<template>
  <div>
    <sticky :offset="46">
      <tab>
        <tab-item :selected="tabIndex === 0" @on-item-click="tabIndex = 0">工作日报</tab-item>
        <tab-item :selected="tabIndex === 1" @on-item-click="tabIndex = 1">批阅</tab-item>
      </tab>
    </sticky>

    <group v-show="false">
      <calendar ref="calDate" title="" v-model="date" @on-change="changeDate"></calendar>
    </group>

    <div v-show="tabIndex === 0">
      <group :gutter="0">
        <cell v-for="(item, index) in list" :key="index" 
          :link="{name: 'DailyView', params: {id: item.id}}">
          <div slot="title">
            <span>{{item.title}}</span>
          </div>
          <span style="font-size:0.8em">{{item.start_time|formatTime}}</span>
          <div slot="inline-desc">
            {{item.summary}}
          </div>
        </cell>
      </group>

      <div style="height:50px;">
        <load-more :show-loading="isLoading" v-show="isLoading || isEnd" :tip="loadingTip"></load-more>
      </div>
    </div>

    <div v-show="tabIndex === 1">
      <group :gutter="0">
        <cell v-for="(item, index) in reviewList" :key="index" :title="item.user"
          :link="{name: 'ReviewView', params: {id: item.id }}">
          <img slot="icon" :src="item.avatar" class="cell-image">
          <span style="font-size:0.8em">
            {{item.create_time|formatTime}}<br />
            <span :class="{red:item.level == 0, green:item.level == 2}">[{{item.levelText}}]</span>
          </span>
          <div slot="inline-desc">
            {{item.content}}
          </div>
        </cell>
      </group>

      <div style="height:50px;">
        <load-more :show-loading="isReviewLoading" v-show="isReviewLoading" tip="正在加载"></load-more>
      </div>
    </div>

    <div v-if="me.id > 0 && me.id == user.superior_id" class="bottom-bar">
      <x-button type="primary" class="bottom-btn" @click.native="review" :disabled="list.length == 0">
        <x-icon type="checkmark-round" class="btn-icon"></x-icon> 批阅
      </x-button>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex'
import { Calendar } from 'vux'

export default {
  components: {
    Calendar
  },
  data () {
    return {
      me: {
        id: 0
      },
      user: {
        title: '',
        superior_id: 0
      },
      tabIndex: 0,
      id: 0,
      date: '',
      isLoading: false,
      isReviewLoading: false,
      page: 0,
      isEnd: false,
      list: [],
      reviewList: []
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      vm.me = vm.$store.state.oice.user || vm.me
      let id = parseInt(to.params.id)
      if (!isNaN(id)) {
        if (to.params.date) {
          vm.date = to.params.date
        } else {
          vm.date = vm.$dateFormat(new Date(), 'YYYY-M-D')
        }
        vm.id = id
        vm.getUserInfo()
        vm.loadListData(true)
        vm.loadReviewData()
      }
    })
  },
  beforeRouteUpdate (to, from, next) {
    let vm = this
    if (to.params.date) {
      vm.date = to.params.date
    } else {
      vm.date = vm.$dateFormat(new Date(), 'YYYY-M-D')
    }
    vm.loadListData(true)
    vm.loadReviewData()
    next()
  },
  methods: {
    new () {
      this.$router.push({name: 'DailyEdit', params: {id: 0}})
    },
    updateTitle () {
      this.$emit('on-view-loaded', this.user.title + ' ' + this.$dateFormat(this.date, 'M月D日'))
    },
    getUserInfo () {
      this.$post('/api/user/detail', {
        id: this.id
      }, (res) => {
        if (res.success) {
          this.user = res.data
          this.updateTitle()
        }
      })
    },
    search () {
      this.$refs.calDate.onClick()
    },
    changeDate () {
      this.$router.push({name: 'DailyUser', params: { id: this.id, date: this.date }})
    },
    loadListData (empty) {
      if (empty) {
        this.isEnd = false
        this.page = 1
        this.list = []
      }
      this.isLoading = true
      this.$post('/api/daily/user', {
        id: this.id,
        date: this.date,
        page: this.page
      }, (res) => {
        this.isLoading = false
        if (res.success) {
          let newData = res.data
          if (!newData || newData.length < 10) {
            this.isEnd = true
          }
          for (let item in newData) {
            this.list.push(newData[item])
          }
        }
      })
    },
    loadReviewData () {
      this.reviewList = []
      this.isReviewLoading = true
      this.$post('/api/daily/review', {
        id: this.id,
        date: this.date
      }, (res) => {
        this.isReviewLoading = false
        if (res.success) {
          this.reviewList = res.data
        }
      })
    },
    review () {
      this.$router.push({name: 'DailyReview', params: { id: 0, user: this.id, date: this.date }})
    }
  },
  watch: {
    scrollBottom (isBottom) {
      if (isBottom && this.$route.name === 'DailyUser' &&
        !this.isLoading && !this.isEnd && this.tabIndex === 0) {
        this.page++
        this.loadListData()
      }
    }
  },
  computed: {
    ...mapState({
      scroolTop: state => state.oice.scroolTop,
      scrollBottom: state => state.oice.scrollBottom
    }),
    loadingTip () {
      if (this.isLoading) {
        return '正在加载'
      } else if (this.isEnd) {
        return this.list.length ? '没有更多了' : '暂无数据'
      } else {
        return ''
      }
    }
  }
}
</script>

<style lang="less">
.red {
  color:red;
}
.green {
  color: green;
}
.vux-cell-bd {
  overflow: hidden;
  .vux-label-desc > div {
    white-space: nowrap;
    overflow: hidden;
    text-overflow : ellipsis 
  }
}
</style>