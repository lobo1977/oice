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
      <tab>
        <tab-item :selected="type == 'follow'" @on-item-click="getList('follow')">跟进客户</tab-item>
        <tab-item :selected="type == 'potential'" @on-item-click="getList('potential')">潜在客户</tab-item>
        <tab-item :selected="type == 'history'" @on-item-click="getList('history')">历史客户</tab-item>
      </tab>
      <router-view></router-view>
    </div>
  </div>
</template>

<script>
import { Search, Tab, TabItem } from 'vux'

export default {
  components: {
    Search,
    Tab,
    TabItem
  },
  data () {
    return {
      type: 'follow',
      isSearching: false,
      results: []
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      vm.type = to.name.toLowerCase()
    })
  },
  beforeRouteUpdate (to, from, next) {
    this.type = to.name.toLowerCase()
    next()
  },
  methods: {
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
    getList (path) {
      this.type = path
      this.$router.push('/customer/' + path)
    }
  }
}
</script>

<style lang="less">
</style>