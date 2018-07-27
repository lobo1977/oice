<template>
  <div>
    <topalert v-if="info.user_id == 0" type="info"
      message="该项目尚未被认领，您可以通过修改完善项目信息完成认领。"></topalert>

    <swiper v-if="info.images.length" :auto="true" :loop="true" height="260px"
      :show-dots="info.images.length > 1">
      <swiper-item class="swiper-img" v-for="(item, index) in info.images" :key="index">
        <img :src="item.src" @click="preview(index)">
      </swiper-item>
    </swiper>

    <div v-transfer-dom v-if="info.images.length">
      <previewer :list="info.images" ref="previewer" :options="previewOptions"></previewer>
    </div>

    <sticky :offset="46">
      <tab>
        <tab-item @on-item-click="goTab(0)" :selected="tab === 0">基本信息</tab-item>
        <tab-item @on-item-click="goTab(1)" :selected="tab === 1">联系人</tab-item>
        <tab-item @on-item-click="goTab(2)" :selected="tab === 2">单元销控</tab-item>
        <tab-item v-if="user != null && user.id > 0" @on-item-click="goTab(3)" :selected="tab === 3">确认书</tab-item>
      </tab>
    </sticky>

    <div v-show="tab === 0">
      <group gutter="0" label-width="4em" label-margin-right="1em" label-align="right">
        <cell title="项目类型" value-align="left" :value="info.building_type" v-show="info.building_type"></cell>
        <cell title="地址" value-align="left" :value="info.location + info.address" v-show="info.location || info.address" 
          :is-link="(info.longitude || info.latitude) != 0" @click.native="showMap = ((info.longitude || info.latitude) != 0)"></cell>
        <cell title="竣工日期" value-align="left" :value="info.completionDate|formatDate" v-show="info.completionDate"></cell>
        <cell title="租售" value-align="left" :value="info.rent_sell" v-show="info.rent_sell"></cell>
        <cell title="价格" value-align="left" :value="info.price" v-show="info.price"></cell>
        <cell title="楼层" value-align="left" :value="info.floor" v-show="info.floor"></cell>
        <cell title="层面积" value-align="left" :value="info.floorArea + ' 平方米'" v-show="info.floorArea > 0"></cell>
        <cell title="层高" value-align="left" :value="info.floorHeight + ' 米'" v-show="info.floorHeight > 0 && info.floorHeight != 2"></cell>
        <cell title="楼板承重" value-align="left" :value="info.bearing + ' 千克/平方米'" v-show="info.bearing > 0"></cell>
        <cell title="开发商" value-align="left" :value="info.developer" v-show="info.developer"></cell>
        <cell title="物业管理" value-align="left" :value="info.manager" v-show="info.manager"></cell>
        <cell title="物业费" value-align="left" :value="info.fee" v-show="info.fee"></cell>
        <cell title="电费" value-align="left" :value="info.electricity_fee" v-show="info.electricity_fee"></cell>
        <cell title="停车位" value-align="left" :value="info.car_seat" v-show="info.car_seat"></cell>
      </group>

      <group title="项目说明" v-show="info.rem">
        <p class="group-padding">{{info.rem}}</p>
      </group>
      <group title="交通状况" v-show="info.traffic">
        <p class="group-padding">{{info.traffic}}</p>
      </group>
      <group title="楼宇设备" v-show="info.equipment">
        <p class="group-padding">{{info.equipment}}</p>
      </group>
      <group title="配套设施" v-show="info.facility">
        <p class="group-padding">{{info.facility}}</p>
      </group>
      <group title="周边环境" v-show="info.environment">
        <p class="group-padding">{{info.environment}}</p>
      </group>

      <flexbox :gutter="0" class="bottom-bar">
        <flexbox-item :span="4">
          <x-button type="warn" class="bottom-btn"
            @click.native="toCustomer(0)">
              <x-icon type="funnel" class="btn-icon"></x-icon> 加入筛选
          </x-button>
        </flexbox-item>
        <flexbox-item :span="4">
          <x-button type="primary" class="bottom-btn"
            @click.native="toCustomer(1)">
              <x-icon type="checkmark-circled" class="btn-icon"></x-icon> 云确认
          </x-button>
        </flexbox-item>
        <flexbox-item :span="2">
          <x-button type="default" class="bottom-btn"
            @click.native="favorite">
            <x-icon type="star" class="btn-icon" :class="{green: info.isFavorite}"></x-icon>
          </x-button>
        </flexbox-item>
        <flexbox-item>
          <x-button type="default" class="bottom-btn" 
            :disabled="info.user_id > 0 && info.user_id != user.id && info.company_id > 0 && info.company_id != user.company_id"
            :link="{name:'BuildingEdit', params: { id: info.id }}">
            <x-icon type="compose" class="btn-icon"></x-icon>
          </x-button>
        </flexbox-item>
      </flexbox>

      <actionsheet v-model="showBuildingMenu" :menus="buildingMenu" theme="android" 
        @on-click-menu="buildingMenuClick"></actionsheet>
    </div>

    <div v-show="tab === 1">
      <cell v-for="(item, index) in info.linkman" :key="index"
        :title="item.title" :link="{name: 'Linkman', params: {id: item.id}}" 
        :inline-desc="item.desc"></cell>
      
      <div class="bottom-bar">
        <x-button type="primary" class="bottom-btn" :disabled="info.id === 0"
          :link="{name: 'LinkmanEdit', params: {id: 0, type: 'building', oid: info.id}}">
          <x-icon type="plus" class="btn-icon"></x-icon> 添加
        </x-button>
      </div>
    </div>

    <div v-show="tab === 2">
      <checker v-model="selectedUnit" type="checkbox" @on-change="selectUnit"
        class="unit-checker-box" default-item-class="unit-item" 
        selected-item-class="unit-item-selected" disabled-item-class="unit-item-disabled">
        <div v-for="(building, b) in unitTree" :key="b">
          <divider v-if="building.building">{{building.building}}</divider>
          <div v-for="(floor, f) in building.floor" :key="f">
            <h5 v-if="floor.floor">{{floor.floor|formatFloor}}</h5>
            <checker-item v-for="(unit, index) in floor.unit" :key="index" :value="unit.id">
              <div :class="{active: touchUnit == unit}" @touchstart="unitTouch(unit, true)" @touchmove="unitTouchMove(true)" @touchend="unitTouchEnd(true)"
                @mousedown="unitTouch(unit)" @mousemove="unitTouchMove(false)" @mouseup="unitTouchEnd(false)">
                #{{unit.room}}/{{unit.acreage}}
              </div>
            </checker-item>
          </div>
        </div>
      </checker>

      <div v-show="selectedUnit.length" style="padding:10px 15px;font-size:0.8em">
        <span>已选中 {{selectedUnit.length}} 个单元</span>
      </div>

      <actionsheet v-model="showUnitMenu" :menus="unitMenu" theme="android" 
        @on-click-menu="unitMenuClick" @on-after-hide="menuUnit = null"></actionsheet>

      <flexbox :gutter="0" class="bottom-bar">
        <flexbox-item :span="4">
          <x-button type="warn" class="bottom-btn" :disabled="selectedUnit.length === 0"
            @click.native="toCustomer(0)">
            <x-icon type="funnel" class="btn-icon"></x-icon> 添加筛选
          </x-button>
        </flexbox-item>
        <flexbox-item :span="4">
          <x-button type="primary" class="bottom-btn" :disabled="selectedUnit.length === 0"
            @click.native="batchFavorite">
            <x-icon type="star" class="btn-icon"></x-icon> 收藏
          </x-button>
        </flexbox-item>
        <flexbox-item>
          <x-button type="default" class="bottom-btn" :disabled="info.id === 0"
            :link="{name: 'UnitEdit', params: { id:0, bid: info.id }}">
            <x-icon type="plus" class="btn-icon"></x-icon> 添加
          </x-button>
        </flexbox-item>
      </flexbox>
    </div>

    <div v-show="tab === 3">
      <cell v-for="(item, index) in info.confirm" :key="index"
        :title="item.title" :link="{name: 'ConfirmView', params: {id: item.id}}" 
        :inline-desc="item.desc"></cell>
    </div>

    <popup-picker ref="customerPicker" class="popup-picker" :show.sync="showCustomerPicker" 
      popup-title="选择客户" :show-cell="false" :data="myCustomer" v-model="selectCustomer"
      @on-hide="customerSelected"></popup-picker>
    
    <popup v-model="showMap" position="bottom"
      height="100%" style="overflow-y:hidden;">
      <baidumap :get-point="false"
        :is-shown="showMap"
        :title="info.building_name"
        :address="info.address"
        :district="info.location"
        :longitude="info.longitude" 
        :latitude="info.latitude" 
        @on-close="closeMap"></baidumap>
    </popup>
  </div>
</template>

<script>
import { Swiper, SwiperItem, Previewer, TransferDom, Sticky, Tab, TabItem,
  Group, GroupTitle, Cell, Divider, Checker, CheckerItem, Actionsheet,
  Flexbox, FlexboxItem, XButton, PopupPicker, Popup, dateFormat } from 'vux'
import Topalert from '../Common/Topalert.vue'
import Baidumap from '../Common/BaiduMap.vue'

export default {
  directives: {
    TransferDom
  },
  components: {
    Swiper,
    SwiperItem,
    Previewer,
    Sticky,
    Tab,
    TabItem,
    Group,
    GroupTitle,
    Cell,
    Divider,
    Checker,
    CheckerItem,
    Actionsheet,
    Flexbox,
    FlexboxItem,
    XButton,
    PopupPicker,
    Popup,
    Topalert,
    Baidumap
  },
  data () {
    return {
      user: {
        id: 0,
        company_id: 0
      },
      tab: 0,
      info: {
        id: 0,
        building_name: '',    // 名称
        building_type: '',    // 类型
        location: '',         // 位置
        address: '',          // 地址
        longitude: 0,         // 经度
        latitude: 0,          // 纬度
        completionDate: '',   // 竣工日期
        rent_sell: '',        // 租售
        price: '',            // 价格
        acreage: 0,           // 建筑面积
        floor: '',            // 楼层
        floorArea: 0,         // 层面积
        floorHeight: 0,       // 层高
        bearing: 0,           // 楼板承重
        developer: '',        // 开发商
        manager: '',          // 物业管理
        fee: '',              // 物业费
        electricity_fee: '',  // 电费
        car_seat: '',         // 停车位
        rem: '',              // 项目说明
        facility: '',         // 配套设施
        equipment: '',        // 楼宇设备
        traffic: '',          // 交通状况
        environment: '',      // 周边环境
        user_id: 0,
        company_id: 0,
        isFavorite: false,
        images: [],
        linkman: [],
        unit: [],
        confirm: []
      },

      showCustomerPicker: false,
      myCustomer: [],
      selectCustomer: [],
      customerFlag: 0,
      previewOptions: {
      },
      showBuildingMenu: false,
      showUnitMenu: false,
      menuUnit: null,
      touchUnit: null,
      unitTouchEvent: 0,
      selectedUnit: [],
      touchX: 0,
      touchY: 0,
      moveX: 0,
      moveY: 0,
      showMap: false
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      vm.user = vm.$store.state.oice.user

      let id = parseInt(to.params.id)
      if (to.query.tab) {
        vm.tab = parseInt(to.query.tab)
        if (isNaN(vm.tab)) {
          vm.tab = 0
        }
      }
      if (!isNaN(id)) {
        vm.$get('/api/building/detail?id=' + id, (res) => {
          if (res.success) {
            for (let item in vm.info) {
              if (res.data[item] !== undefined && res.data[item] !== null) {
                vm.info[item] = res.data[item]
              }
            }
            vm.info.building_type = res.data.type
            if (res.data.level) {
              vm.info.building_type += `（${res.data.level}级）`
            }
            if (res.data.area) {
              vm.info.location = res.data.area
            }
            if (res.data.district) {
              vm.info.location += res.data.district
            }
            vm.info.completionDate = res.data.completion_date
            if (res.data.isFavorite) {
              vm.info.isFavorite = true
            }

            if (res.data.customer) {
              let customer = []
              for (let i in res.data.customer) {
                customer.push({
                  name: res.data.customer[i].customer_name,
                  value: res.data.customer[i].id
                })
              }
              if (customer.length) {
                customer.push({
                  name: '新客户',
                  value: 0
                })
                vm.myCustomer.push(customer)
              }
            }
            vm.$emit('on-view-loaded', vm.info.building_name)

            if (vm.$isWechat()) {
              let shareLink = window.location.href
              let shareDesc = (res.data.level ? res.data.level + '级' : '') + res.data.type +
                  ' ' + vm.info.location + ' ' + vm.info.price
              let shareImage = null

              if (vm.info.images.length) {
                shareImage = window.location.protocol + '//' +
                  window.location.host + vm.info.images[0].src
              }

              vm.$wechatShare(null, shareLink, vm.info.building_name, shareDesc, shareImage)
            }
          } else {
            vm.info.id = 0
            vm.$vux.toast.show({
              text: res.message,
              width: '15em'
            })
          }
        })
      }
    })
  },
  methods: {
    goTab (tab) {
      this.$router.replace({name: 'BuildingView', id: this.info.id, query: {tab: tab}})
      this.tab = tab
    },
    preview (index) {
      this.$refs.previewer.show(index)
    },
    favorite () {
      if (!this.user) {
        this.$router.push({
          name: 'Login',
          query: { redirect: this.$route.fullPath }
        })
      }
      this.$vux.loading.show()
      if (this.info.isFavorite) {
        this.$post('/api/building/unFavorite', {
          id: this.info.id
        }, (res) => {
          this.$vux.loading.hide()
          if (res.success) {
            this.info.isFavorite = false
            this.$vux.toast.show({
              type: 'success',
              text: '已从收藏夹移除。',
              width: '12em'
            })
          } else {
            this.$vux.toast.show({
              text: res.message,
              width: '15em'
            })
          }
        })
      } else {
        this.$post('/api/building/favorite', {
          id: this.info.id
        }, (res) => {
          this.$vux.loading.hide()
          if (res.success) {
            this.info.isFavorite = true
            this.$vux.toast.show({
              type: 'success',
              text: '已添加到收藏夹。',
              width: '12em'
            })
          } else {
            this.$vux.toast.show({
              text: res.message,
              width: '15em'
            })
          }
        })
      }
    },
    closeMap () {
      this.showMap = false
    },
    selectUnit () {
    },
    toCustomer (flag) {
      if (!this.user) {
        this.$router.push({
          name: 'Login',
          query: { redirect: this.$route.fullPath }
        })
      }
      this.customerFlag = flag
      if (this.customerFlag === 1 && this.tab === 2 && this.selectedUnit.length > 1) {
        this.$vux.toast.show({
          text: '只能选择一个单元生成客户确认书。',
          width: '18em'
        })
      } else if (this.myCustomer.length) {
        this.showCustomerPicker = true
      } else {
        if (this.tab === 0) {
          this.$router.push({
            name: 'CustomerEdit',
            params: {id: 0},
            query: {bid: this.info.id, flag: this.customerFlag === 0 ? 'filter' : 'confirm'}
          })
        } else {
          this.$router.push({
            name: 'CustomerEdit',
            params: {id: 0},
            query: {uid: this.selectedUnit.join(','), flag: this.customerFlag === 0 ? 'filter' : 'confirm'}
          })
        }
      }
    },
    customerSelected (isConfirm) {
      if (!isConfirm || this.selectCustomer.length <= 0) return
      let customerId = this.selectCustomer[0]
      let bid = 0
      let uids = ''
      if (this.tab === 0) {
        bid = this.info.id
        if (customerId === '0' || customerId === 0) {
          this.$router.push({
            name: 'CustomerEdit',
            params: {id: 0},
            query: {bid: bid, flag: this.customerFlag === 0 ? 'filter' : 'confirm'}
          })
          return
        }
      } else {
        if (this.selectedUnit.length <= 0) return
        uids = this.selectedUnit.join(',')
        if (customerId === '0' || customerId === 0) {
          this.$router.push({
            name: 'CustomerEdit',
            params: {id: 0},
            query: {uid: uids, flag: this.customerFlag === 0 ? 'filter' : 'confirm'}
          })
          return
        }
      }
      if (this.customerFlag === 1) {
        this.toConfirm(customerId, bid)
      } else {
        this.toFilter(customerId, bid, uids)
      }
    },
    toFilter (cid, bids, uids) {
      this.$vux.loading.show()
      this.$post('/api/customer/addFilter', {
        cid: cid,
        bids: bids,
        uids: uids
      }, (res) => {
        this.$vux.loading.hide()
        if (res.success) {
          this.info.isFavorite = true
          this.$vux.toast.show({
            type: 'success',
            text: '已添加到客户筛选表。',
            width: '12em'
          })
        } else {
          this.$vux.toast.show({
            text: res.message,
            width: '15em'
          })
        }
      })
    },
    toConfirm (cid, bid) {
      this.$router.push({name: 'ConfirmEdit', params: {id: 0, bid: bid, cid: cid}})
    },
    batchFavorite () {
      if (this.user) {
        if (this.selectedUnit.length <= 0) {
          return
        }
        this.$vux.loading.show()
        this.$post('/api/building/batchFavorite', {
          ids: this.selectedUnit
        }, (res) => {
          this.$vux.loading.hide()
          if (res.success) {
            this.$vux.toast.show({
              type: 'success',
              text: '已添加到收藏夹。',
              width: '13em'
            })
          } else {
            this.$vux.toast.show({
              text: res.message,
              width: '15em'
            })
          }
        })
      } else {
        this.$router.push({
          name: 'Login',
          query: { redirect: this.$route.fullPath }
        })
      }
    },
    buildingMenuClick (key, item) {
      let vm = this
      if (key === 'edit') {
        vm.$router.push({name: 'BuildingEdit', params: {id: vm.info.id}})
      } else if (key === 'delete' && vm.info.user_id === vm.user.id) {
        // vm.$vux.confirm.show({
        //   title: '删除项目',
        //   content: '确定要删除这个项目吗？',
        //   onConfirm () {
        //     vm.$vux.loading.show()
        //     vm.$post('/api/building/remove', {
        //       id: vm.info.id
        //     }, (res) => {
        //       vm.$vux.loading.hide()
        //       if (res.success) {
        //         vm.$router.back()
        //       } else {
        //         vm.$vux.toast.show({
        //           text: res.message,
        //           width: '13em'
        //         })
        //       }
        //     })
        //   }
        // })
      }
    },
    unitTouch (item, isTouch) {
      let vm = this
      vm.touchUnit = item
      if (isTouch) {
        event.preventDefault()
        if (event.touches) {
          vm.touchX = event.touches[0].pageX
          vm.touchY = event.touches[0].pageY
        } else if (event.changedTouches) {
          vm.touchX = event.changedTouches[0].pageX
          vm.touchY = event.changedTouches[0].pageY
        } else if (event.targetTouches) {
          vm.touchX = event.targetTouches[0].pageX
          vm.touchY = event.targetTouches[0].pageY
        }
        vm.moveX = vm.touchX
        vm.moveY = vm.touchY
      }
      vm.unitTouchEvent = setTimeout(() => {
        vm.unitTouchEvent = 0
        if (!isTouch || (Math.abs(vm.moveX - vm.touchX) <= 5 && Math.abs(vm.moveY - vm.touchY) <= 5)) {
          vm.menuUnit = item
          vm.showUnitMenu = true
        }
      }, 500)
    },
    unitTouchMove (isTouch) {
      if (isTouch) {
        if (event.touches) {
          this.moveX = event.touches[0].pageX
          this.moveY = event.touches[0].pageY
        } else if (event.changedTouches) {
          this.moveX = event.changedTouches[0].pageX
          this.moveY = event.changedTouches[0].pageY
        } else if (event.targetTouches) {
          this.moveX = event.targetTouches[0].pageX
          this.moveY = event.targetTouches[0].pageY
        }
      } else {
        this.touchUnit = null
        clearTimeout(this.unitTouchEvent)
        this.unitTouchEvent = 0
      }
    },
    unitTouchEnd (isTouch) {
      this.touchUnit = null
      clearTimeout(this.unitTouchEvent)
      if (this.unitTouchEvent !== 0 && isTouch) {
        if (event.changedTouches) {
          this.moveX = event.changedTouches[0].pageX
          this.moveY = event.changedTouches[0].pageY
        } else if (event.targetTouches) {
          this.moveX = event.targetTouches[0].pageX
          this.moveY = event.targetTouches[0].pageY
        }
        if (Math.abs(this.moveX - this.touchX) <= 5 && Math.abs(this.moveY - this.touchY) <= 5) {
          let e = document.createEvent('MouseEvents')
          e.initEvent('click', true, true)
          event.target.dispatchEvent(e)
        }
      }
      return false
    },
    unitMenuClick (key, item) {
      let vm = this
      if (vm.menuUnit != null) {
        let unitId = vm.menuUnit.id
        if (key === 'view') {
          vm.$router.push({name: 'Unit', params: {id: unitId}})
        } else if (key === 'edit') {
          vm.$router.push({name: 'UnitEdit', params: {id: unitId, bid: this.info.id}})
        } else if (key === 'delete' &&
          vm.menuUnit.user_id === vm.user.id) {
          vm.$vux.confirm.show({
            title: '删除单元',
            content: '确定要删除这个单元吗？',
            onConfirm () {
              vm.$vux.loading.show()
              vm.$post('/api/unit/remove', {
                id: unitId,
                bid: vm.info.id
              }, (res) => {
                vm.$vux.loading.hide()
                if (res.success) {
                  vm.info.unit = res.data
                  vm.selectedUnit = []
                } else {
                  vm.$vux.toast.show({
                    text: res.message,
                    width: '13em'
                  })
                }
              })
            }
          })
        }
      }
    }
  },
  computed: {
    unitTree () {
      let tree = []
      if (!this.info.unit || !this.info.unit.length) {
        return tree
      }
      let building = {}
      let floor = {}
      let b, f
      for (let i in this.info.unit) {
        if (b !== this.info.unit[i].building_no) {
          b = this.info.unit[i].building_no
          f = null
          building = {building: b, floor: []}
          tree.push(building)
        }
        if (f !== this.info.unit[i].floor) {
          f = this.info.unit[i].floor
          floor = {floor: f, unit: []}
          building.floor.push(floor)
        }
        floor.unit.push(this.info.unit[i])
      }
      return tree
    },
    buildingMenu () {
      if (this.info.user_id && this.user && this.info.user_id === this.user.id) {
        return {
          edit: '修改',
          delete: '删除'
        }
      } else {
        return {
          edit: '修改'
        }
      }
    },
    unitMenu () {
      if (this.menuUnit && this.user) {
        if (this.menuUnit.user_id === this.user.id) {
          return {
            view: '查看',
            edit: '修改',
            delete: '删除'
          }
        } else {
          return {
            view: '查看',
            edit: '修改'
          }
        }
      } else {
        return []
      }
    },
    favoriteText () {
      return this.info.isFavorite ? '取消收藏' : '收藏'
    }
  },
  filters: {
    formatDate (date) {
      if (date) {
        return dateFormat(new Date(Date.parse(date.replace(/-/g, '/'))), 'YYYY年M月D日')
      } else {
        return ''
      }
    },
    formatFloor (floor) {
      if (floor > 0) {
        return floor + '层'
      } else if (floor < 0) {
        return '地下' + Math.abs(floor) + '层'
      } else {
        return ''
      }
    }
  }
}
</script>

<style lang="less">
.swiper-img {
  text-align:center;
  overflow: hidden;
  img {
    margin:0 auto;
    height:260px;
  }
}

.unit-checker-box {
  padding-left:10px;
  padding-right:15px;
  padding-bottom:10px;
  background-color:#fff;
  .vux-divider {
    padding: 15px 0 0 0
  }
  h5 { padding-top:10px; }
}

.unit-item {
  margin-top: 10px;
  margin-left: 5px;
  line-height: 1.4em;
  text-align: center;
  color: #222;
  border: 1px solid #ccc;
  background-color: #fff;
  border-radius: 3px;
  div {
    padding: 5px 10px;
    user-select: none;
  }
  div.active {
    background-color: #ECECEC;
  }
}

.unit-item-selected {
  background-color: rgb(6, 165, 27);
  color: #fff;
}

.unit-item-disabled {
  background-color:#ccc;
}
</style>