<template>
  <div>
    <panel :list="list" :type="listType" @on-img-error="onImgError"></panel>
    <div style="height:50px;">
      <load-more :show-loading="isLoading" @click.native="loadMore" :tip="loadingTip"></load-more>
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
      type: 'follow',
      isLoading: false,
      listType: '2',
      page: 1,
      isEnd: false,
      list: []
    }
  },
  methods: {
    onImgError (item, $event) {
    },
    loadMore () {
      if (!this.isLoading && !this.isEnd) {
        this.page++
        this.loadData()
      }
    },
    loadData (empty) {
      if (empty) {
        this.isEnd = false
        this.page = 1
        this.list = []
      }
      this.isLoading = true
      this.$post('/api/customer/index', {
        page: this.page,
        type: this.type
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
        } else {
          this.$vux.toast.show({
            text: res.message,
            width: '13em'
          })
        }
      })
    },
    export () {
      if (this.$store.state.oice.user && this.list.length) {
        let token = this.$store.state.oice.user.token
        let url = '/api/customer/export?user-token=' + token + '&type=' + this.type
        this.$download(url)
      }
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      vm.type = to.name.toLowerCase()
      vm.loadData(true)
    })
  },
  watch: {
    scrollBottom (isBottom) {
      if (isBottom &&
        (this.$route.name === 'Follow' ||
        this.$route.name === 'Potential' ||
        this.$route.name === 'Pool')) {
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
