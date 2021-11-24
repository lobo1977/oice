<template>
  <div>
    <group :gutter="0">
      <cell v-for="(item, index) in list" :key="index" :title="item.title" 
        :link="{name: 'DailyUser', params: { id: item.id, date: date }}">
        <img slot="icon" :src="item.avatar" class="cell-image">
        <div slot="inline-desc">
          <span>今日 {{item.daily_count}} 项日报</span>
        </div>
      </cell>
    </group>

    <div style="height:50px;">
      <load-more :show-loading="isLoading" @click.native="loadMore" :tip="loadingTip"></load-more>
    </div>

    <service></service>
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
      date: '',
      isLoading: false,
      page: 0,
      isEnd: false,
      list: []
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      vm.user = vm.$store.state.oice.user || vm.user
      vm.date = vm.$dateFormat(new Date(), 'YYYY-M-D')
      vm.loadListData(true)
    })
  },
  methods: {
    new () {
      this.$router.push({name: 'DailyEdit', params: {id: 0}})
    },
    loadMore () {
      if (!this.isLoading && !this.isEnd) {
        this.page++
        this.loadListData()
      }
    },
    loadListData (empty) {
      if (empty) {
        this.isEnd = false
        this.page = 1
        this.list = []
      }
      this.isLoading = true
      this.$post('/api/daily/index', {
        page: this.page,
        date: this.date
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
      if (isBottom && this.$route.name === 'Daily') {
        this.loadMore()
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
        return '加载更多'
      }
    }
  }
}
</script>

<style lang="less">
</style>