<template>
  <div>
    <group :gutter="0">
      <cell v-for="(item, index) in list" :key="index" :title="item.title"
        :link="{name: 'CustomerView', params: {id: item.id}}">
        <p slot="inline-desc" class="cell-desc">{{item.desc}}</p>
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
      page: 0,
      isLoading: false,
      isEnd: false,
      list: []
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
    })
  },
  methods: {
    loadListData (empty) {
      if (empty) {
        this.isEnd = false
        this.page = 1
        this.list = []
      }
      this.isLoading = true
      this.$get('/api/my/task?page=' + this.page, (res) => {
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
    scrollBottom (isBottom) {
      if (isBottom && this.$route.name === 'MyCustomer' &&
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