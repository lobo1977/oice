<template>
  <div>
    <topalert v-if="info.id > 0 && info.user_id == 0" type="info"
      message="该项目尚未被认领，您可以通过修改完善项目信息完成认领。"></topalert>

    <swiper v-if="info.videos.length || info.images.length" :auto="false" :loop="true" height="260px"
      :show-dots="info.videos.length + info.images.length > 1">
      <swiper-item class="swiper-img" v-for="(item, index) in info.videos" :key="index">
        <img src="/static/img/play.png" @click="previewVideo(index)"
          style="position:absolute;top:82px;left:50%;margin-left:-48px;width:96px;height:96px;">
        <img :src="item.msrc" height="100%" @click="previewVideo(index)">
      </swiper-item>
      <swiper-item class="swiper-img" v-for="(item, index) in info.images" :key="info.videos.length + index">
        <img :src="item.src" height="100%" @click="preview(index)">
      </swiper-item>
    </swiper>

    <div v-transfer-dom v-if="info.images.length">
      <previewer :list="info.images" ref="prevBuilding" :options="previewOptions"></previewer>
    </div>

    <div v-show="!showPush">
      <group gutter="0" label-width="4em" label-margin-right="1em" label-align="right">
        <!-- <cell title="项目类型" value-align="left" :value="info.building_type" v-show="info.building_type"></cell> -->
        <cell value-align="left" :value="info.area + info.address" v-show="info.area || info.address" 
          :is-link="true" @click.native="showMap = true">
          <span slot="title"><x-icon type="android-pin" size="22" style="position:relative;top:4px;"></x-icon> <span>地址</span></span>
        </cell>
      </group>

      <flexbox :gutter="0" class="button-bar">
        <flexbox-item :span="4">
          <x-button type="default"
            @click.native="favorite">
            <x-icon type="star" class="btn-icon" :class="{green: info.isFavorite}"></x-icon> 收藏
          </x-button>
        </flexbox-item>
        <flexbox-item :span="4">
          <x-button type="default"
            @click.native="notes">
            生成笔记
          </x-button>
        </flexbox-item>
        <flexbox-item>
          <x-button type="default" :disabled="!info.allowEdit"
            :link="{name:'BuildingEdit', params: { id: info.id }}">
            <x-icon type="compose" class="btn-icon"></x-icon> 编辑
          </x-button>
        </flexbox-item>
      </flexbox>

      <group v-if="info.allowEdit || (info.unit && info.unit.length)">
        <group-title slot="title">单元销控
          <router-link style="float:right;color:#333;cursor:pointer;" v-if="info.allowEdit"
            :to="{name: 'UnitEdit', params: { id:0, bid: info.id }}">+ 添加</router-link>
        </group-title>
        <cell v-for="(item, index) in showUnits" :key="index" :title="item.title" :is-link="true" @click.native="unitClick(item)">
          <img slot="icon" :src="item.src" class="cell-image" />
          <p slot="inline-desc" class="cell-desc">{{item.desc}}</p>
        </cell>
      </group>

      <group v-if="user == null || user.id == 0 || info.allowEdit || (info.linkman && info.linkman.length)">
        <group-title slot="title">项目通讯录
          <router-link style="float:right;color:#333;cursor:pointer;" v-if="info.allowEdit"
            :to="{name: 'LinkmanEdit', params: {id: 0, type: 'building', oid: info.id}}">+ 添加</router-link>
        </group-title>
        <div v-if="user != null && user.id > 0 && info.linkman && info.linkman.length">
          <cell v-for="(item, index) in info.linkman" :key="index"
            :title="item.title">
            <a v-if="!info.allowEdit" v-bind:href="'tel:'+(item.mobile || item.tel)" class="cell-link"><x-icon type="iphone" class="cell-icon" style="margin-right:30px;cursor:pointer;fill:blue;"></x-icon></a>
            <x-icon v-if="!info.allowEdit" type="wechat" class="cell-icon" style="margin-right:30px;cursor:pointer;" @click="copyWeixin(item.weixin || item.mobile)"></x-icon>
            <x-icon v-if="info.allowEdit" type="compose" class="cell-icon" style="margin-right:30px;cursor:pointer;" @click="editLinkman(item)"></x-icon>
            <x-icon v-if="info.allowEdit" type="trash-a" class="cell-icon" style="margin-right:30px;cursor:pointer;fill:red;" @click="deleteLinkman(index, item)"></x-icon>
          </cell>
        </div>

        <div v-if="user == null || user.id == 0">
          <load-more
            :show-loading="false" tip="请登录后查看" background-color="#fbf9fe"></load-more>
            <div style="text-align:center;margin-bottom:30px;">
              <x-button type="primary" @click.native="login" 
                style="display:inline-block;width:auto;">
                立即登录
              </x-button>
            </div>
        </div>
      </group>

      <group title="项目简介" label-width="4em" label-margin-right="1em" label-align="right">
        <cell title="竣工日期" value-align="left" :value="info.completion_date_text" v-show="info.completion_date_text"></cell>
        <cell title="租售" value-align="left" :value="info.rent_sell" v-show="info.rent_sell"></cell>
        <cell title="价格" value-align="left" :value="info.price" v-show="info.price"></cell>
        <cell title="建筑面积" value-align="left" :value="info.acreage + '平方米'" v-show="info.acreage > 0"></cell>
        <!-- <cell title="楼层" value-align="left" :value="info.floor" v-show="info.floor"></cell>
        <cell title="层面积" value-align="left" :value="info.floor_area + ' 平方米'" v-show="info.floor_area > 0"></cell> -->
        <cell title="层高" value-align="left" :value="info.floor_height + ' 米'" v-show="info.floor_height > 0"></cell>
        <cell title="楼板承重" value-align="left" :value="info.bearing + ' 千克/平方米'" v-show="info.bearing > 0"></cell>
        <cell title="开发商" value-align="left" :value="info.developer" v-show="info.developer"></cell>
        <cell title="物业管理" value-align="left" :value="info.manager" v-show="info.manager"></cell>
        <cell title="物业费" value-align="left" :value="info.fee" v-show="info.fee"></cell>
        <!-- <cell title="电费" value-align="left" :value="info.electricity_fee" v-show="info.electricity_fee"></cell>
        <cell title="停车位" value-align="left" :value="info.car_seat" v-show="info.car_seat"></cell> -->
        <p class="group-padding" v-show="info.rem">{{info.rem}}</p>
        <p class="group-padding" v-show="info.traffic">交通状况：{{info.traffic}}</p>
        <p class="group-padding" v-show="info.equipment">楼宇设备：{{info.equipment}}</p>
        <p class="group-padding" v-show="info.facility">配套设施：{{info.facility}}</p>
        <p class="group-padding" v-show="info.environment">周边环境：{{info.environment}}</p>
      </group>
    </div>

    <qrcode></qrcode>

    <actionsheet v-model="showUnitMenu" :menus="unitMenu" theme="android" 
      @on-click-menu="unitMenuClick" @on-after-hide="menuUnit = null"></actionsheet>

    <flexbox :gutter="0" class="bottom-bar">
      <flexbox-item :span="4">
        <x-button type="warn"
          @click.native="toCustomer(info.id)">
            <x-icon type="funnel" class="btn-icon"></x-icon> 加入筛选
        </x-button>
      </flexbox-item>
      <flexbox-item :span="4">
        <x-button type="primary"
          @click.native="push()">
            <x-icon type="android-share" class="btn-icon"></x-icon> 群发推广
        </x-button>
      </flexbox-item>
      <flexbox-item :span="4">
        <x-button type="default"
          @click.native="add()">
            <x-icon type="plus" class="btn-icon"></x-icon> 发布项目
        </x-button>
      </flexbox-item>
      <!-- <flexbox-item :span="4">
        <x-button type="primary"
          @click.native="toCustomer(1)">
            <x-icon type="checkmark-circled" class="btn-icon"></x-icon> 云确认
        </x-button> -->
    </flexbox>

    <popup-picker class="popup-picker" :show.sync="showCustomerPicker" 
      popup-title="选择客户" :show-cell="false" :data="myCustomer" v-model="selectCustomer"
      @on-hide="customerSelected"></popup-picker>
    
    <popup v-model="showMap" position="bottom"
      height="100%" style="overflow-y:hidden;">
      <baidumap :get-point="false"
        :is-shown="showMap"
        :title="info.building_name"
        :district="info.area"
        :address="info.address"
        :longitude="info.longitude" 
        :latitude="info.latitude" 
        @on-close="closeMap"></baidumap>
    </popup>

    <popup v-model="showPush" position="bottom"
      height="100%" style="overflow-y:hidden;">
      <robotpush
        :is-shown="showPush"
        :content="pushContent"
        :url="info.short_url"
        @on-close="closePush"></robotpush>
    </popup>

    <popup v-model="showVideo" position="bottom" height="100%">
      <x-header
        style="width:100%;position:fixed;left:0;top:0;z-index:100;"
        :left-options="leftHeaderOptions"
        :right-options="rightHeaderOptions"
        @on-click-back.prevent="showVideo = false">
        <a slot="right" :href="video_src" style="margin-right:10px;" download="video" title="下载">
          <x-icon type="ios-download" size="24" class="header-icon"></x-icon>
        </a>
      </x-header>
      <div style="display:flex;display:-webkit-flex;align-items:center;height:100%;overflow-y:hidden;background-color:#000000;">
        <video :src="video_src" :poster="video_msrc" width="100%" controls="controls" muted="muted" 
          x5-video-player-type="h5" x5-video-player-fullscreen="true"></video>
      </div>
    </popup>
  </div>
</template>

<script>
import { Swiper, SwiperItem, Previewer, TransferDom, GroupTitle, XHeader } from 'vux'
import Topalert from '@/components/Topalert.vue'
import Baidumap from '@/components/BaiduMap.vue'
import Robotpush from '@/components/RobotPush.vue'
import qrcode from '@/components/qrcode.vue'

export default {
  directives: {
    TransferDom
  },
  components: {
    Swiper,
    SwiperItem,
    Previewer,
    GroupTitle,
    Topalert,
    Baidumap,
    Robotpush,
    qrcode,
    XHeader
  },
  data () {
    return {
      user: {
        id: 0
      },
      // tab: 0,
      info: {
        id: 0,
        building_name: '',    // 名称
        building_type: '',    // 类型
        area: '',             // 城区
        district: '',         // 商圈
        address: '',          // 地址
        longitude: 0,         // 经度
        latitude: 0,          // 纬度
        completion_date_text: '',  // 竣工日期
        rent_sell: '',        // 租售
        price: '',            // 价格
        acreage: 0,           // 建筑面积
        // floor: '',            // 楼层
        // floor_area: 0,        // 层面积
        floor_height: 0,      // 层高
        bearing: 0,           // 楼板承重
        developer: '',        // 开发商
        manager: '',          // 物业管理
        fee: '',              // 物业费
        // electricity_fee: '',  // 电费
        // car_seat: '',         // 停车位
        rem: '',              // 项目说明
        facility: '',         // 配套设施
        equipment: '',        // 楼宇设备
        traffic: '',          // 交通状况
        environment: '',      // 周边环境
        user_id: 0,
        short_url: '',
        key: '',
        isFavorite: false,
        allowEdit: false,
        allowDelete: false,
        images: [],
        videos: [],
        linkman: [],
        unit: [],
        confirm: [],
        shareList: []
      },
      shareLink: window.location.href,
      shareImage: null,
      shareDesc: '',
      showCustomerPicker: false,
      myCustomer: [],
      selectCustomer: [],
      customerFlag: 0,
      previewOptions: {
      },
      showBuildingMenu: false,
      showUnitMenu: false,
      menuUnit: null,
      tempUnitId: 0,
      showMap: false,
      showPush: false,
      wxImages: [],
      showVideo: false,
      video_src: '',
      video_msrc: '',
      leftHeaderOptions: {
        preventGoBack: true
      },
      rightHeaderOptions: {
      }
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      vm.user = vm.$store.state.oice.user || vm.user
      let id = parseInt(to.params.id)
      let key = (to.params.key ? to.params.key : '')
      if (!isNaN(id)) {
        vm.$vux.loading.show()
        vm.$get('/api/building/detail?id=' + id + '&key=' + key, (res) => {
          vm.$vux.loading.hide()
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

            vm.shareLink = 'https://m.o-ice.com/app/building/view/' + vm.info.id + '/' + vm.info.key
            vm.setShareDesc()

            if (vm.info.images.length) {
              for (let i = 0; i < vm.info.images.length; i++) {
                vm.wxImages.push(window.location.protocol + '//' +
                window.location.host + vm.info.images[i].src)
              }

              vm.shareImage = vm.wxImages[0]
            }

            if (vm.$isWechat()) {
              vm.$wechatShare(null, vm.shareLink, vm.info.building_name, vm.shareDesc, vm.shareImage)
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
    login () {
      this.$checkAuth()
    },
    add () {
      this.$router.push({name: 'BuildingEdit'})
    },
    push () {
      if (this.user != null && this.user.id > 0) {
        this.showPush = true
      } else {
        this.login()
      }
    },
    copyWeixin (text) {
      let vm = this
      vm.$copyText(text).then(function (e) {
        vm.$vux.toast.show({
          type: 'success',
          text: '微信号已复制到剪贴板',
          width: '12em'
        })
      })
    },
    closePush () {
      this.showPush = false
    },
    setShareDesc () {
      let vm = this
      vm.shareDesc = vm.info.building_type + ' ' + vm.info.area + vm.info.district + ' ' + vm.info.price
    },
    preview (index) {
      if (this.$isWechat()) {
        this.$previewImage(this.wxImages[index], this.wxImages)
      } else {
        this.$refs.prevBuilding.show(index)
      }
    },
    previewVideo (index) {
      this.video_src = this.info.videos[index].src
      this.video_msrc = this.info.videos[index].msrc
      this.showVideo = true
    },
    downloadVideo () {
      document.location.href = this.video_src
    },
    favorite () {
      if (!this.$checkAuth()) {
        return
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
    notes () {
      document.location = '/index/building/' + this.info.id
    },
    closeMap () {
      this.showMap = false
    },
    selectUnit () {
    },
    toCustomer (bid, uid) {
      if (!this.$checkAuth()) {
        return
      }

      if (uid) {
        this.tempUnitId = uid
      } else {
        this.tempUnitId = 0
      }

      if (this.myCustomer.length) {
        this.showCustomerPicker = true
      } else {
        if (bid) {
          this.$router.push({
            name: 'CustomerEdit',
            params: {id: 0},
            query: {bid: this.info.id, flag: 'filter'}
          })
        } else if (uid) {
          this.$router.push({
            name: 'CustomerEdit',
            params: {id: 0},
            query: {uid: uid, flag: 'filter'}
          })
        }
      }
    },
    customerSelected (isConfirm) {
      if (!isConfirm || this.selectCustomer.length <= 0) return
      let customerId = this.selectCustomer[0]
      let bid = 0
      let uids = ''
      if (this.tempUnitId === 0) {
        bid = this.info.id
        if (customerId === '0' || customerId === 0) {
          this.$router.push({
            name: 'CustomerEdit',
            params: {id: 0},
            query: {bid: bid, flag: 'filter'}
          })
          return
        }
      } else {
        uids = this.tempUnitId
        if (customerId === '0' || customerId === 0) {
          this.$router.push({
            name: 'CustomerEdit',
            params: {id: 0},
            query: {uid: uids, flag: 'filter'}
          })
          return
        }
      }
      this.toFilter(customerId, bid, uids)
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
    // toConfirm (cid, bid) {
    //   this.$router.push({name: 'ConfirmEdit', params: {id: 0, bid: bid, cid: cid}})
    // },
    // batchFavorite () {
    //   if (this.selectedUnit.length <= 0) {
    //     return
    //   }
    //   if (!this.$checkAuth()) {
    //     return
    //   }
    //   this.$vux.loading.show()
    //   this.$post('/api/building/batchFavorite', {
    //     ids: this.selectedUnit
    //   }, (res) => {
    //     this.$vux.loading.hide()
    //     if (res.success) {
    //       this.$vux.toast.show({
    //         type: 'success',
    //         text: '已添加到收藏夹。',
    //         width: '13em'
    //       })
    //     } else {
    //       this.$vux.toast.show({
    //         text: res.message,
    //         width: '15em'
    //       })
    //     }
    //   })
    // },
    buildingMenuClick (key, item) {
      let vm = this
      if (key === 'edit') {
        vm.$router.push({name: 'BuildingEdit', params: {id: vm.info.id}})
      } else if (key === 'delete' && vm.info.allowDelete) {
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
    unitClick (item) {
      this.menuUnit = item
      let menuLength = 0
      let menuKey = ''
      if (this.unitMenu != null) {
        for (var key in this.unitMenu) {
          menuKey = key
          menuLength++
        }
        this.showUnitMenu = menuLength > 1
        if (menuLength === 1) {
          this.unitMenuClick(menuKey, this.unitMenu[menuKey])
        }
      } else {
        this.showUnitMenu = false
      }
    },
    unitMenuClick (key, item) {
      let vm = this
      if (vm.menuUnit != null) {
        let unitId = vm.menuUnit.id
        if (key === 'view') {
          if (vm.menuUnit.allowView) {
            vm.$router.push({name: 'Unit', params: {id: unitId}})
          } else if (vm.user != null && vm.user.id > 0) {
            vm.$vux.toast.show({
              text: '此单元未公开，不能查看详情。',
              width: '13em'
            })
          } else {
            vm.login()
          }
        } else if (key === 'favorite') {
          if (!vm.$checkAuth()) {
            return
          }
          vm.$post('/api/unit/favorite', {
            id: unitId
          }, (res) => {
            vm.$vux.loading.hide()
            if (res.success) {
              vm.$vux.toast.show({
                type: 'success',
                text: '已添加到收藏夹。',
                width: '13em'
              })
            } else {
              vm.$vux.toast.show({
                text: res.message,
                width: '15em'
              })
            }
          })
        } else if (key === 'filter') {
          if (!vm.$checkAuth()) {
            return
          }
          vm.toCustomer(0, unitId)
        } else if (key === 'edit') {
          vm.$router.push({name: 'UnitEdit', params: {id: unitId, bid: this.info.id}})
        } else if (key === 'delete') {
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
                } else {
                  vm.$vux.toast.show({
                    text: res.message,
                    width: '13em'
                  })
                }
              })
            }
          })
        } else if (key === 'notes') {
          document.location = '/index/unit/' + unitId
        }
      }
      vm.menuUnit = null
    },
    editLinkman (item) {
      let vm = this
      vm.$router.push({name: 'LinkmanEdit', params: {id: item.id}})
    },
    deleteLinkman (index, item) {
      let vm = this

      if (!vm.$checkAuth() || !vm.info.allowEdit) {
        return
      }

      vm.$vux.confirm.show({
        title: '删除联系人',
        content: '确定要删除联系人 <strong>' + item.title + '</strong> 吗？',
        onConfirm () {
          vm.$vux.loading.show({text: '请稍后...'})
          vm.$post('/api/linkman/remove', {
            id: item.id
          }, (res) => {
            vm.$vux.loading.hide()
            if (res.success) {
              vm.info.linkman.splice(index, 1)
            } else {
              vm.$vux.toast.show({
                text: res.message,
                width: '13em'
              })
            }
          })
        }
      })
    },
    playerOptions (src, posterSrc) {
      return {
        muted: true,
        language: 'en',
        height: 260,
        playbackRates: [0.7, 1.0, 1.5, 2.0],
        sources: [{
          type: 'video/mp4',
          src: src
        }],
        poster: posterSrc
      }
    }
  },
  computed: {
    showUnits () {
      let vm = this
      if (vm.info && vm.info.unit) {
        return this.info.unit.filter(function (unit) {
          return unit.status === 1 || vm.info.allowEdit
        })
      } else {
        return null
      }
    },
    buildingMenu () {
      let menu = {}
      if (this.info.allowEdit) {
        menu.edit = '修改'
      }
      if (this.info.allowDelete) {
        menu.delete = '删除'
      }
      return menu
    },
    unitMenu () {
      if (this.menuUnit) {
        let menu = {
          view: '查看',
          favorite: '收藏',
          filter: '添加筛选'
        }
        if (this.menuUnit.allowEdit) {
          menu.edit = '修改'
        }
        if (this.menuUnit.allowDelete) {
          menu.delete = '删除'
        }
        menu.notes = '生成笔记'
        return menu
      } else {
        return null
      }
    },
    favoriteText () {
      return this.info.isFavorite ? '取消收藏' : '收藏'
    },
    pushContent () {
      let vm = this
      if (vm.info) {
        let content = '【项目名称】' + vm.info.building_name
        // if (vm.info.area || vm.info.district || vm.info.address) {
        content += '\n【地理位置】' + vm.info.area + vm.info.district + vm.info.address
        // }
        content += '\n【空置面积】'
        // if (vm.info.traffic) {
        content += '\n【交 通】' + vm.info.traffic
        // }
        // if (vm.info.price) {
        content += '\n【报 价】' + vm.info.price
        // }
        // if (vm.info.fee) {
        content += '\n【物业费】' + vm.info.fee
        // }
        content += '\n【佣 金】'
        content += '\n【交付标准】'
        if (vm.info.linkman && vm.info.linkman.length) {
          content += '\n【联系人】' + vm.info.linkman[0].title
          content += '\n【联系电话】' + vm.info.linkman[0].mobile
        }
        return content
      } else {
        return ''
      }
    }
  },
  filters: {
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