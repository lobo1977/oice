<template>
  <div>
    <panel :list="list" :type="listType" @on-img-error="onImgError"></panel>
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
    loadListData (empty) {
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
        }
      })
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      vm.type = to.name.toLowerCase()
      vm.loadListData(true)
    })
  },
  watch: {
    scrollBottom (isBottom) {
      if (isBottom &&
        (this.$route.name === 'Potential' ||
        this.$route.name === 'Follow' ||
        this.$route.name === 'History') &&
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
</style>
