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

    <actionsheet v-model="showNewMenu" :menus="newMenus" theme="android" @on-click-menu="newMenuClick"></actionsheet>

    <form ref="frmCustomer" style="display:none">
      <input ref="inpFile" type="file" name="data" @change="upLoad"
        accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel">
    </form>

    <div v-show="!isSearching">
      <tab>
        <tab-item :selected="type == 'follow'" @on-item-click="getList('follow')">跟进客户</tab-item>
        <tab-item :selected="type == 'potential'" @on-item-click="getList('potential')">潜在客户</tab-item>
        <tab-item :selected="type == 'pool'" @on-item-click="getList('pool')">客户池</tab-item>
      </tab>
      <router-view ref="list"></router-view>
    </div>
  </div>
</template>

<script>
import { Search } from 'vux'

export default {
  components: {
    Search
  },
  data () {
    return {
      user: {
        id: 0
      },
      showNewMenu: false,
      newMenus: {
        new: '添加客户',
        template: '下载批量导入模板',
        import: '批量导入客户',
        export: '导出'
      },
      type: 'follow',
      isSearching: false,
      results: []
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      vm.user = vm.$store.state.oice.user || vm.user
      vm.type = to.name.toLowerCase()
    })
  },
  beforeRouteUpdate (to, from, next) {
    this.type = to.name.toLowerCase()
    next()
  },
  methods: {
    new () {
      this.showNewMenu = true
    },
    newMenuClick (key, item) {
      if (key === 'new') {
        this.$router.push({name: 'CustomerEdit', params: {id: 0}})
      } else if (key === 'template') {
        document.location.href = '/static/template/customer.xlsx'
      } else if (key === 'import') {
        if (!this.user || this.user.id === 0) {
          this.$router.push({
            name: 'Login',
            query: { redirect: this.$route.fullPath }
          })
        } else {
          this.$refs.inpFile.click()
        }
      } else if (key === 'export') {
        if (!this.user || this.user.id === 0) {
          this.$router.push({
            name: 'Login',
            query: { redirect: this.$route.fullPath }
          })
        } else {
          this.$refs.list.export()
        }
      }
    },
    upLoad () {
      let vm = this
      let src = vm.$refs.inpFile
      if (src.files && src.files[0]) {
        let form = vm.$refs.frmCustomer
        vm.$vux.loading.show()
        vm.$postFile('/api/customer/import', form, (res) => {
          try {
            src.value = ''
          } catch (e) {}
          vm.$vux.loading.hide()
          if (res.success) {
            let message = ''
            let success = res.data.success
            let clash = res.data.clash
            let fail = res.data.fail
            if (success > 0) {
              message = '成功导入 ' + success + ' 条客户资料'
            }
            if (clash > 0) {
              if (message) {
                message += '，'
              }
              message += '由于客户资料重复 ' + clash + ' 条资料导入失败'
            }
            if (fail > 0) {
              if (message) {
                message += '，'
              }
              message += '由于客户资料不完整 ' + fail + ' 条资料导入失败'
            }
            message += '。'
            vm.$vux.alert.show({
              title: '操作完成',
              content: message,
              onHide () {
                if (success > 0) {
                  vm.getList('pool')
                }
              }
            })
          } else {
            vm.$vux.toast.show({
              text: res.message,
              width: '15em'
            })
          }
        })
      }
    },
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
      if (this.type === path) {
        this.$refs.list.loadData(true)
      } else {
        this.$router.push('/customer/' + path)
      }
    }
  }
}
</script>

<style lang="less">
</style>