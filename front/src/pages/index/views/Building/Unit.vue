<template>
  <div>
    <swiper :auto="true" :loop="true" height="260px"
      :show-dots="info.images.length > 1" v-show="info.images.length > 0">
      <swiper-item class="swiper-img" v-for="(item, index) in info.images" :key="index">
        <img :src="item.src" @click="preview(index)">
      </swiper-item>
    </swiper>

    <div v-transfer-dom>
      <previewer :list="info.images" ref="prevUnit" :options="previewOptions"></previewer>
    </div>

    <group gutter="0" label-width="4em" label-margin-right="1em" label-align="right">
      <cell title="朝向" value-align="left" :value="info.face" v-if="info.face"></cell>
      <cell title="面积" value-align="left" :value="info.acreage + ' 平方米'" v-if="info.acreage"></cell>
      <cell title="租售" value-align="left" :value="info.rent_sell" v-if="info.rent_sell"></cell>
      <cell title="出租价格" value-align="left" :value="info.rent_price + ' 元/平方米/日'" v-if="info.rent_price"></cell>
      <cell title="出售价格" value-align="left" :value="info.sell_price + ' 元/平方米'" v-if="info.sell_price"></cell>
      <cell title="装修状况" value-align="left" :value="info.decoration" v-if="info.decoration"></cell>
      <cell title="状态" value-align="left" :value="info.statusText" v-if="info.statusText"></cell>
      <cell title="到期日" value-align="left" :value="info.end_date_text" v-if="info.end_date_text"></cell>
    </group>

    <group title="备注" v-show="info.rem">
      <p class="group-padding">{{info.rem}}</p>
    </group>

    <group v-if="info.linkman.length || info.allowEdit">
      <group-title slot="title">联系人
        <router-link style="float:right;color:#333;" v-if="info.allowEdit"
          :to="{name: 'LinkmanEdit', params: {id: 0, type: 'unit', oid: info.id}}">+ 添加</router-link>
      </group-title>
      <cell v-for="(item, index) in info.linkman" :key="index"
        :title="item.title" :link="{name: 'Linkman', params: {id: item.id}}" 
        :inline-desc="item.desc"></cell>
    </group>

    <qrcode></qrcode>

    <flexbox :gutter="0" class="bottom-bar">
      <flexbox-item :span="5">
        <x-button type="warn" class="bottom-btn"
          @click.native="toCustomer(0)">
            <x-icon type="funnel" class="btn-icon"></x-icon> 加入筛选
        </x-button>
      </flexbox-item>
      <flexbox-item :span="5">
        <x-button type="primary" class="bottom-btn"
          @click.native="favorite">
          <x-icon type="star" class="btn-icon" :class="{green: info.isFavorite}"></x-icon>
          {{favoriteText}}
        </x-button>
      </flexbox-item>
      <flexbox-item>
        <x-button type="default" class="bottom-btn"
          :disabled="!info.allowNew && !info.allowEdit && !info.allowDelete"
          @click.native="showUnitMenu = true">
          <x-icon type="ios-more" class="btn-icon"></x-icon>
        </x-button>
      </flexbox-item>
    </flexbox>

    <popup-picker class="popup-picker" :show.sync="showCustomerPicker" 
      popup-title="选择客户" :show-cell="false" :data="myCustomer" v-model="selectCustomer"
      @on-hide="customerSelected"></popup-picker>

    <actionsheet v-model="showUnitMenu" :menus="unitMenu" theme="android" 
      @on-click-menu="unitMenuClick"></actionsheet>
  </div>
</template>

<script>
import { Swiper, SwiperItem, Previewer, TransferDom, GroupTitle } from 'vux'
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
    qrcode
  },
  data () {
    return {
      info: {
        id: 0,
        title: '',
        building_name: '',
        building_id: 0,
        building_no: '',
        floor: 0,          // 楼层
        room: '',          // 房间号
        face: '',          // 朝向
        acreage: 0,        // 面积
        rent_sell: '',     // 租售
        rent_price: 0,     // 出租价格
        sell_price: 0,     // 出售价格
        decoration: '',    // 装修状况
        status: 0,         // 状态
        statusText: '',
        end_date_text: '',      // 到日期
        rem: '',           // 备注
        key: '',
        isFavorite: false,
        allowNew: false,
        allowEdit: false,
        allowDelete: false,
        images: [],
        linkman: []
      },
      previewOptions: {
      },
      showCustomerPicker: false,
      myCustomer: [],
      selectCustomer: [],
      customerFlag: 0,
      showUnitMenu: false,
      wxImages: []
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      let id = parseInt(to.params.id)
      if (!isNaN(id)) {
        vm.$get('/api/unit/detail?id=' + id, (res) => {
          if (res.success) {
            for (let item in vm.info) {
              if (res.data[item] !== undefined && res.data[item] !== null) {
                vm.info[item] = res.data[item]
              }
            }
            vm.info.title = vm.info.building_name + vm.info.title
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
            vm.$emit('on-view-loaded', vm.info.title)

            if (vm.$isWechat()) {
              let shareLink = 'https://m.o-ice.com/app/unit/view/' + vm.info.id + '/' + vm.info.key
              let shareDesc = (vm.info.acreage ? vm.info.acreage + ' 平方米 ' : '') +
                (vm.info.rent_price ? vm.info.rent_price + ' 元/平方米/日 ' : '') +
                vm.info.decoration
              let shareImage = null

              if (vm.info.images.length) {
                for (let i = 0; i < vm.info.images.length; i++) {
                  vm.wxImages.push(window.location.protocol + '//' +
                  window.location.host + vm.info.images[i].src)
                }

                shareImage = vm.wxImages[0]
              }

              vm.$wechatShare(null, shareLink, vm.info.title, shareDesc, shareImage)
            }
          } else {
            vm.info.id = 0
            vm.$vux.toast.show({
              text: res.message,
              width: '15em'
            })
          }
        })
      } else {
        vm.$emit('on-view-loaded', '单元信息')
      }
    })
  },
  methods: {
    preview (index) {
      if (this.$isWechat()) {
        this.$previewImage(this.wxImages[index], this.wxImages)
      } else {
        this.$refs.prevUnit.show(index)
      }
    },
    favorite () {
      if (!this.$checkAuth()) {
        return
      }
      this.$vux.loading.show()
      if (this.info.isFavorite) {
        this.$post('/api/unit/unFavorite', {
          id: this.info.id
        }, (res) => {
          this.$vux.loading.hide()
          if (res.success) {
            this.info.isFavorite = false
            this.$vux.toast.show({
              type: 'success',
              text: '已从收藏夹移除。',
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
        this.$post('/api/unit/favorite', {
          id: this.info.id
        }, (res) => {
          this.$vux.loading.hide()
          if (res.success) {
            this.info.isFavorite = true
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
      }
    },
    toCustomer (flag) {
      if (!this.$checkAuth()) {
        return
      }
      this.customerFlag = flag
      if (this.myCustomer.length) {
        this.showCustomerPicker = true
      } else {
        this.$router.push({
          name: 'CustomerEdit',
          params: {id: 0},
          query: {uid: this.info.id, flag: this.customerFlag === 0 ? 'filter' : 'confirm'}
        })
      }
    },
    customerSelected (isConfirm) {
      if (!isConfirm || this.selectCustomer.length <= 0) return
      let customerId = this.selectCustomer[0]
      if (customerId === 0) {
        this.$router.push({
          name: 'CustomerEdit',
          params: {id: 0},
          query: {uid: this.info.id, flag: this.customerFlag === 0 ? 'filter' : 'confirm'}
        })
      } else if (this.customerFlag === 1) {
        this.toConfirm(customerId, this.info.id)
      } else {
        this.toFilter(customerId, this.info.id)
      }
    },
    toFilter (cid, id) {
      this.$vux.loading.show()
      this.$post('/api/customer/addFilter', {
        cid: cid,
        bids: 0,
        uids: id
      }, (res) => {
        this.$vux.loading.hide()
        if (res.success) {
          this.info.isFavorite = true
          this.$vux.toast.show({
            type: 'success',
            text: '已添加到客户筛选表。',
            width: '13em'
          })
        } else {
          this.$vux.toast.show({
            text: res.message,
            width: '15em'
          })
        }
      })
    },
    toConfirm (cid, id) {
    },
    unitMenuClick (key, item) {
      let vm = this
      if (key === 'new') {
        vm.$router.push({name: 'UnitEdit', params: {id: 0, bid: vm.info.building_id}})
      } else if (key === 'edit') {
        vm.$router.push({name: 'UnitEdit', params: {id: vm.info.id, bid: vm.info.building_id}})
      } else if (key === 'delete') {
        vm.remove()
      } else if (key === 'notes') {
        document.location = '/index/unit/' + this.info.id
      }
    },
    remove () {
      let vm = this
      this.$vux.confirm.show({
        title: '删除单元',
        content: '确定要删除单元 <strong>' + vm.info.title + '</strong> 吗？',
        onConfirm () {
          vm.$vux.loading.show()
          vm.$post('/api/unit/remove', {
            id: vm.info.id
          }, (res) => {
            vm.$vux.loading.hide()
            if (res.success) {
              vm.$router.back()
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
  },
  computed: {
    favoriteText () {
      return this.info.isFavorite ? '取消收藏' : '收藏'
    },
    unitMenu () {
      let menu = {}
      if (this.info.allowNew) {
        menu.new = '添加'
      }
      if (this.info.allowEdit) {
        menu.edit = '修改'
      }
      if (this.info.allowDelete) {
        menu.delete = '删除'
      }
      menu.notes = '生成笔记'
      return menu
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
</style>