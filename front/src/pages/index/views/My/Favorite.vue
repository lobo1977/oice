<template>
  <div>
    <group :gutter="0">
      <cell v-for="(item, index) in list" :key="index" :title="item.title" :link="item.url">
        <img slot="icon" :src="item.src" class="cell-image" />
        <p slot="inline-desc" class="cell-desc">{{item.desc}}</p>
        <div solt="default">
          <check-icon :value.sync="item.checked" @update:value="check"></check-icon>
        </div>
      </cell>
    </group>

    <div style="height:50px;">
      <load-more :show-loading="isLoading" v-show="isLoading || isEnd" :tip="loadingTip"></load-more>
    </div>

    <popup-picker class="popup-picker" :show.sync="showCustomerPicker" 
      popup-title="选择客户" :show-cell="false" :data="myCustomer" v-model="selectCustomer"
      @on-hide="addFilter"></popup-picker>
    
    <flexbox :gutter="0" class="bottom-bar">
      <flexbox-item :span="8">
        <x-button type="primary" class="bottom-btn" :disabled="checkCount <= 0"
          @click.native="toFilter">
          <x-icon type="social-youtube" class="btn-icon"></x-icon> 加入筛选
        </x-button>
      </flexbox-item>
      <flexbox-item>
        <x-button type="warn" class="bottom-btn" :disabled="checkCount <= 0"
          @click.native="remove">
          <x-icon type="trash-a" class="btn-icon"></x-icon> 移除
        </x-button>
      </flexbox-item>
    </flexbox>
  </div>
</template>

<script>
import { CheckIcon } from 'vux'
import { mapState } from 'vuex'

export default {
  components: {
    CheckIcon
  },
  data () {
    return {
      page: 0,
      isLoading: false,
      isEnd: false,
      customer_id: 0,
      checkCount: 0,
      list: [],
      showCustomerPicker: false,
      myCustomer: [],
      selectCustomer: [],
      selectedBuildingIds: [],
      selectedUnitIds: []
    }
  },
  methods: {
    loadListData (empty) {
      if (empty) {
        this.isEnd = false
        this.page = 1
        this.list = []
      }
      this.isLoading = true
      this.$get('/api/my/favorite?page=' + this.page, (res) => {
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
    },
    check (checked) {
      checked ? this.checkCount++ : this.checkCount--
      return false
    },
    getSelectedList () {
      let selectedList = []
      this.selectedBuildingIds = []
      this.selectedUnitIds = []
      this.list.forEach((item, index) => {
        if (item.checked) {
          selectedList.push('' + item.building_id + ',' + item.unit_id)
          if (item.unit_id) {
            this.selectedUnitIds.push(item.unit_id)
          } else {
            this.selectedBuildingIds.push(item.building_id)
          }
        }
      })
      return selectedList
    },
    remove () {
      if (this.checkCount <= 0) {
        return
      }
      this.$vux.loading.show()
      this.$post('/api/building/batchUnFavorite', {
        ids: this.getSelectedList()
      }, (res) => {
        this.$vux.loading.hide()
        if (res.success) {
          for (let i = this.list.length - 1; i >= 0; i--) {
            if (this.list[i].checked) {
              this.list.splice(i, 1)
            }
          }
        } else {
          this.$vux.toast.show({
            text: res.message,
            width: '15em'
          })
        }
      })
    },
    toFilter () {
      let vm = this
      if (vm.customer_id === 0) {
        if (this.myCustomer.length) {
          this.showCustomerPicker = true
          return
        }
        vm.$vux.toast.show({
          text: '请选择一个客户。',
          width: '12em',
          onHide () {
            vm.$router.push({name: 'Customer'})
          }
        })
      } else if (vm.checkCount <= 0) {
        vm.$vux.toast.show({
          text: '请选择要加入筛选的项目。',
          width: '15em'
        })
      } else {
        vm.$vux.loading.show()
        vm.getSelectedList()
        vm.$post('/api/customer/addFilter', {
          cid: vm.customer_id,
          bids: this.selectedBuildingIds.join(','),
          uids: this.selectedUnitIds.join(',')
        }, (res) => {
          vm.$vux.loading.hide()
          if (res.success) {
            vm.$vux.toast.show({
              type: 'success',
              text: '已加入客户筛选表。',
              width: '13em',
              onHide () {
                vm.$router.push({
                  name: 'CustomerView',
                  params: {id: vm.customer_id},
                  query: {tab: 2}
                })
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
    addFilter (isConfirm) {
      if (!isConfirm || this.selectCustomer.length <= 0) return
      this.customer_id = this.selectCustomer[0]
      this.toFilter()
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      if (to.query.cid) {
        vm.customer_id = parseInt(to.query.cid)
        if (isNaN(vm.customer_id)) {
          vm.customer_id = 0
        }
      }
    })
  },
  mounted: function () {
    this.$get('/api/my/customer', (res) => {
      if (res.success) {
        let customer = []
        for (let i in res.data) {
          customer.push({
            name: res.data[i].customer_name,
            value: res.data[i].id
          })
        }
        this.myCustomer.push(customer)
      }
    })
    this.loadListData(true)
  },
  watch: {
    scrollBottom (isBottom) {
      if (isBottom && this.$route.name === 'Favorite' &&
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