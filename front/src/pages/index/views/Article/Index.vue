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
      ref="searchArticle"></search>

    <div v-show="!isSearching">
      <sticky :offset="46">
        <tab :scroll-threshold=4>
          <tab-item v-for="(item, index) in arrType" :key="index" 
          :selected="type == item.value" @on-item-click="getList(item.value)">{{item.label}}</tab-item>
        </tab>
      </sticky>
      <router-view ref="listArticle"></router-view>
    </div>
  </div>
</template>

<script>
import { Search } from 'vux'
import articleType from '../../data/article_type.json'

export default {
  components: {
    Search
  },
  data () {
    return {
      arrType: articleType,
      type: 0,
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
    new () {
      this.$router.push({name: 'ArticleEdit', params: {id: 0, type: this.type}})
    },
    resultClick (item) {
      this.$router.push({name: 'ArticleView', params: {id: item.id}})
      this.$refs.searchArticle.setBlur()
      this.isSearching = false
    },
    getResult (val) {
      if (val) {
        this.$post('/api/article/index', {
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
    onSubmit (val) {
      this.$refs.listArticle.setBlur()
      if (val) {
        this.getResult(val)
      }
    },
    onCancel () {
      this.isSearching = false
    },
    getList (type) {
      if (this.type === type) {
        this.$refs.listArticle.loadData(true)
      } else {
        this.type = type
        this.$refs.listArticle.loadData(true, type)
      }
    }
  }
}
</script>

<style lang="less">
</style>