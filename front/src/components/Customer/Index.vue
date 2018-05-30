<template>
  <div>
    <search @result-click="resultClick"
      @on-change="getResult"
      :results="results"
      position="absolute"
      auto-scroll-to-top top="46px"
      @on-focus="onFocus"
      @on-cancel="onCancel"
      @on-submit="onSubmit"
      ref="search"></search>

    <div v-show="!isSearching">
      <panel :list="list" :type="listType" @on-img-error="onImgError"></panel>

      <div style="height:50px;">
        <load-more :show-loading="isLoading" v-show="isLoading || isEnd" :tip="loadingTip"></load-more>
      </div>
    </div>
  </div>
</template>

<script>
import { Search, Panel, LoadMore } from 'vux'
import { mapState } from 'vuex'

export default {
  components: {
    Search,
    Panel,
    LoadMore
  },
  data () {
    return {
      isLoading: false,
      isSearching: false,
      listType: '2',
      page: 0,
      isEnd: false,
      list: [],
      results: []
    }
  },
  methods: {
    // Searcher
    resultClick (item) {
      this.$router.push({name: 'CustomerView', params: {id: item.id}})
      this.$refs.search.setBlur()
      this.isSearching = false
    },
    getResult (val) {
      if (val) {
        this.$post('/api/customer/index', {
          keyword: val
        }, (res) => {
          if (res.success) {
            this.results = res.data
          } else {
            this.results = []
          }
        })
      } else {
        this.results = []
      }
    },
    onFocus () {
      this.isSearching = true
    },
    onSubmit () {
      this.$refs.search.setBlur()
      this.isSearching = false
    },
    onCancel () {
      this.isSearching = false
    },

    // list
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
  mounted: function () {
    this.loadListData(true)
  },
  watch: {
    user () {
      this.loadListData(true)
    },
    scrollBottom (isBottom) {
      if (isBottom && this.$route.name === 'Customer' &&
        !this.isLoading && !this.isEnd) {
        this.page++
        this.loadListData()
      }
    }
  },
  computed: {
    ...mapState({
      user: state => state.oice.user,
      scroolTop: state => state.scroolTop,
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