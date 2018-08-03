<template>
  <div>
    <group :gutter="0">
      <cell v-for="(item, index) in list" :key="index" 
        :title="item.title" :inline-desc="item.mobile"
        :link="{name: 'UserView', params: {id: item.id}}">
        <img slot="icon" :src="item.avatar" class="cell-image" />
      </cell>
    </group>

    <div style="height:50px;">
      <load-more :show-loading="isLoading" v-show="isLoading || isEnd" :tip="loadingTip"></load-more>
    </div>
  </div>
</template>

<script>
import { Group, Cell, LoadMore } from 'vux'
import { mapState } from 'vuex'

export default {
  components: {
    Group,
    Cell,
    LoadMore
  },
  data () {
    return {
      id: 0,
      isLoading: false,
      page: 0,
      isEnd: false,
      list: []
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      let id = parseInt(to.params.id)
      if (!isNaN(id)) {
        vm.id = id
        vm.loadListData(true)
      }
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
      this.$post('/api/user/companyMember', {
        id: this.id,
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
      if (isBottom && this.$route.name === 'CompanyUser' &&
        !this.isLoading && !this.isEnd) {
        this.page++
        this.loadListData()
      }
    }
  },
  computed: {
    ...mapState({
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