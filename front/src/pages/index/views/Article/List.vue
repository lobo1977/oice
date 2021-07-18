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
      type: 0,
      isLoading: false,
      listType: '5',
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
    loadData (empty, type) {
      if (empty) {
        this.isEnd = false
        this.page = 1
        this.list = []
      }
      this.isLoading = true
      this.$post('/api/article/index', {
        page: this.page,
        type: type || this.type
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
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      if (to.params.type) {
        vm.type = parseInt(to.params.type)
      }
      vm.loadData(true)
    })
  },
  watch: {
    scrollBottom (isBottom) {
      if (isBottom &&
        this.$route.name === 'Article') {
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
        return this.list.length ? '没有更多了' : '暂无内容'
      } else {
        return '加载更多'
      }
    }
  }
}
</script>

<style lang="less">
</style>
