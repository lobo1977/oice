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
      ref="searchCustomer"></search>

    <actionsheet v-model="showNewMenu" :menus="newMenus" theme="android" @on-click-menu="newMenuClick"></actionsheet>

    <form ref="frmImportCustomer" style="display:none">
      <input ref="inpCustomerFile" type="file" name="data" @change="upLoad"
        accept="application/vnd.ms-excel">
    </form>

    <div v-show="!isSearching">
      <sticky :offset="46">
        <tab>
          <tab-item :selected="type == 'follow'" @on-item-click="getList('follow')">跟进</tab-item>
          <tab-item :selected="type == 'potential'" @on-item-click="getList('potential')">潜在</tab-item>
          <tab-item :selected="type == 'success'" @on-item-click="getList('success')">成交</tab-item>
          <tab-item :selected="type == 'fail'" @on-item-click="getList('fail')">失败</tab-item>
        </tab>
      </sticky>
      <router-view ref="listCustomer"></router-view>
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
      showNewMenu: false,
      newMenus: {
        new: '添加客户',
        template: '下载批量导入模板',
        import: '批量导入客户'
      },
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
    new () {
      this.showNewMenu = true
    },
    newMenuClick (key, item) {
      if (key === 'new') {
        this.$router.push({name: 'CustomerEdit', params: {id: 0}})
      } else if (key === 'template') {
        window.location.href = '/static/template/customer.xls'
      } else if (key === 'import') {
        if (this.$checkAuth()) {
          this.$refs.inpCustomerFile.click()
        }
      }
    },
    upLoad () {
      let vm = this
      let src = vm.$refs.inpCustomerFile
      if (src.files && src.files[0]) {
        let form = vm.$refs.frmImportCustomer
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
              message += '由于客户资料重复 ' + clash + ' 条导入失败'
            }
            if (fail > 0) {
              if (message) {
                message += '，'
              }
              message += '由于客户资料不完整 ' + fail + ' 条导入失败'
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
    export () {
      if (this.$checkAuth()) {
        this.$refs.listCustomer.export()
      }
    },
    resultClick (item) {
      this.$router.push({name: 'CustomerView', params: {id: item.id}})
      this.$refs.searchCustomer.setBlur()
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
    onSubmit (val) {
      this.$refs.searchCustomer.setBlur()
      if (val) {
        this.getResult(val)
      }
    },
    onCancel () {
      this.isSearching = false
    },
    getList (path) {
      if (this.type === path) {
        this.$refs.listCustomer.loadData(true)
      } else {
        this.$router.push('/customer/' + path)
      }
    }
  }
}
</script>

<style lang="less">
</style>