<template>
  <div>
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
</template>

<script>
import { mapState } from 'vuex'

export default {
  components: {
  },
  data () {
    return {
      user: {
      },
      id: 0,
      date: '',
      isLoading: false,
      page: 0,
      isEnd: false,
      username: '',
      list: []
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      vm.date = vm.$dateFormat(new Date(), 'YYYY-M-D')
      vm.user = vm.$store.state.oice.user || vm.user
      let id = parseInt(to.params.id)
      if (!isNaN(id)) {
        vm.id = id
        vm.getUserInfo()
        vm.loadListData(true)
      }
    })
  },
  methods: {
    new () {
      this.$router.push({name: 'DailyEdit', params: {id: 0}})
    },
    updateTitle () {
      this.$emit('on-view-loaded', this.username + ' ' + this.$dateFormat(this.date, 'M月D日'))
    },
    getUserInfo () {
      this.$post('/api/user/detail', {
        id: this.id
      }, (res) => {
        if (res.success) {
          this.username = res.data.title
          this.updateTitle()
        }
      })
    },
    search () {
      let vm = this
      vm.$vux.datetime.show({
        cancelText: '取消',
        confirmText: '确定',
        format: 'YYYY-M-D',
        value: vm.date,
        onConfirm (val) {
          vm.date = val
          vm.updateTitle()
          vm.loadListData(true)
        }
      })
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
    }
  },
  watch: {
    scrollBottom (isBottom) {
      if (isBottom && this.$route.name === 'DailyUser' &&
        !this.isLoading && !this.isEnd) {
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
.vux-cell-bd {
  overflow: hidden;
  .vux-label-desc > div {
    white-space: nowrap;
    overflow: hidden;
    text-overflow : ellipsis 
  }
}
</style>