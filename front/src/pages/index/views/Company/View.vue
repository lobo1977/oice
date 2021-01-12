<template>
  <div>
    <masker color="255,255,255" :opacity="1" style="margin:10px;height:130px;border-radius:2px;">
      <div slot="content" class="company-info">
        <div style="margin-bottom:15px;height:60px;">
          <img v-if="info.logo && info.logo.length > 0" :src="info.logo" />
        </div>
        <h4>{{info.title}}</h4>
      </div>
    </masker>

    <div v-transfer-dom>
      <x-dialog v-model="showStamp" hide-on-blur>
        <img :src="info.stampView" style="margin:20px 0;max-width:100%"
          @click="showStamp = false">
      </x-dialog>
    </div>

    <group gutter="0">
      <cell v-if="info.full_name" title="企业全称" :value="info.full_name">
        <x-icon slot="icon" type="android-list" class="cell-icon"></x-icon>
      </cell>
      <cell v-if="info.area && info.address" title="地址" :value="info.area + ' ' + info.address">
        <x-icon slot="icon" type="location" class="cell-icon"></x-icon>
      </cell>
      <cell v-if="info.addin > 0" title="成员" :value="info.addin"
        :link="info.isAddin === 1 ? {name: 'CompanyMember', params: {id: info.id}} : null">
        <x-icon slot="icon" type="ios-people" class="cell-icon"></x-icon>
      </cell>
      <cell title="公章"
        v-if="info.enable_stamp && info.allowEdit && info.stamp != null && info.stamp != ''">
        <x-icon slot="icon" type="ios-circle-filled" class="cell-icon"></x-icon>
        <div solt="default" style="height:60px;line-height:0;">
          <img :src="info.stamp" style="height:60px;" @click.stop="previewStamp">
        </div>
      </cell>
    </group>

    <group title="企业介绍" v-if="info.rem && info.rem.length > 0">
      <p class="group-padding">{{ info.rem }}</p>
    </group>

    <group v-if="info.allowPass && waitUser.length" 
      title="待审核成员" footerTitle="向左滑动条目完成操作" class="wait-group">
      <swipeout>
        <swipeout-item v-for="(item, index) in waitUser" :key="index" transition-mode="follow"
          @on-open="swipeoutOpen(item)" @on-close="swipeoutClose(item)"
          @mousedown.native="itemMouseDown" @mouseup.native="itemMouseUp" 
          @touchstart.native="itemMouseDown" @touchend.native="itemMouseUp"
          @click.native="itemClick(item)">
          <div slot="right-menu">
            <swipeout-button @click.native.stop="audit(item.id, 1)" type="primary">通过</swipeout-button>
            <swipeout-button @click.native.stop="audit(item.id, 0)" type="warn">驳回</swipeout-button>
          </div>
          <cell slot="content" :title="item.title" :inline-desc="item.mobile">
            <img slot="icon" :src="item.avatar" class="cell-image">
          </cell>
        </swipeout-item>
      </swipeout>
    </group>

    <div v-if="!info.allowInvite" class="bottom-bar">
      <x-button v-if="info.isAddin === false" type="primary" class="bottom-btn" 
        :disabled="info.join_way == 2 && !info.isInvtie"
        @click.native="addin">
        <x-icon type="log-in" class="btn-icon"></x-icon>
        <span v-if="info.join_way == 0 && !info.isInvtie">立即加入</span>
        <span v-if="info.join_way == 1 && !info.isInvtie">申请加入</span>
        <span v-if="info.join_way == 2 && !info.isInvtie">需通过邀请加入</span>
        <span v-if="info.isInvtie">接受邀请</span>
      </x-button>
      <x-button v-if="info.isAddin === 1" type="warn" class="bottom-btn" @click.native="quit">
        <x-icon type="log-out" class="btn-icon"></x-icon> 退出
      </x-button>
      <x-button v-if="info.isAddin === 0" type="warn" class="bottom-btn" @click.native="quit">
        <x-icon type="log-out" class="btn-icon"></x-icon> 放弃申请
      </x-button>
    </div>

    <flexbox v-if="info.allowInvite || info.allowEdit || info.allowDelete" :gutter="0" class="bottom-bar">
      <flexbox-item :span="5">
        <x-button type="warn" class="bottom-btn" @click.native="invite" :disabled="!info.allowInvite">
          <x-icon type="log-in" class="btn-icon"></x-icon> 邀请同事
        </x-button>
      </flexbox-item>
      <flexbox-item :span="5">
        <x-button type="primary" class="bottom-btn" 
          :link="{name: 'CompanyEdit', params: {id: info.id}}" :disabled="!info.allowEdit">
          <x-icon type="compose" class="btn-icon"></x-icon> 修改
        </x-button>
      </flexbox-item>
      <flexbox-item :span="2">
        <x-button type="default" class="bottom-btn" @click.native="remove" :disabled="!info.allowDelete">
          <x-icon type="trash-a" class="btn-icon"></x-icon>
        </x-button>
      </flexbox-item>
    </flexbox>
  </div>
</template>

<script>
import { Masker, TransferDom, XDialog, Swipeout, SwipeoutItem, SwipeoutButton } from 'vux'
import { mapActions } from 'vuex'

export default {
  directives: {
    TransferDom
  },
  components: {
    Masker,
    XDialog,
    Swipeout,
    SwipeoutItem,
    SwipeoutButton
  },
  data () {
    return {
      info: {
        id: 0,
        title: '',
        full_name: '',
        logo: '',
        stamp: '',
        stampView: '',
        enable_stamp: 1,
        area: '',
        address: '',
        rem: '',
        join_way: 0,
        addin: 0,
        wait: 0,
        isAddin: false,
        isInvtie: false,
        allowEdit: false,
        allowInvite: false,
        allowPass: false,
        allowDelete: false
      },
      showStamp: false,
      waitUser: [],
      pageX: null,
      pageY: null,
      mouseMove: false
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      let id = parseInt(to.params.id)
      if (!isNaN(id)) {
        vm.getInfo(id)
      }
    })
  },
  methods: {
    ...mapActions([
      'setUser', 'getUser'
    ]),
    getInfo (id) {
      let vm = this
      vm.$get('/api/company/detail?id=' + id, (res) => {
        if (res.success) {
          for (let item in vm.info) {
            if (res.data[item] !== undefined && res.data[item] !== null) {
              vm.info[item] = res.data[item]
            }
          }
          if (res.data.waitUser) {
            vm.waitUser = res.data.waitUser
          }

          if (vm.info.stamp) {
            vm.info.stampView = vm.info.stamp.replace('/60/', '/200/')
          }

          vm.$emit('on-view-loaded', vm.info.title)

          if (vm.$isWechat()) {
            let shareLink = window.location.href
            let shareDesc = '商办云入驻企业'
            let shareImage = null
            if (vm.info.logo) {
              shareImage = window.location.protocol + '//' +
                window.location.host + vm.info.logo
            }

            vm.$wechatShare(null, shareLink, vm.info.full_name, shareDesc, shareImage)
          }
        } else {
          vm.info.id = 0
          vm.$vux.toast.show({
            text: res.message,
            width: '15em'
          })
        }
      })
    },
    previewStamp () {
      if (this.info.stampView) {
        this.showStamp = true
      }
    },
    swipeoutOpen (item) {
      item.disabled = true
    },
    swipeoutClose (item) {
      item.disabled = false
    },
    itemMouseDown (event) {
      this.pageX = event.pageX
      this.pageY = event.pageY
    },
    itemMouseUp (event) {
      if (this.pageX !== event.pageX || this.pageY !== event.pageY) {
        this.mouseMove = true
      } else {
        this.mouseMove = false
      }
    },
    itemClick (item) {
      if (item.disabled || this.mouseMove) {
      } else {
        this.$router.push({name: 'UserView', params: {id: item.id}})
      }
    },
    audit (userId, flag) {
      let vm = this
      let url = '/api/company/passAddin'
      if (!flag) {
        url = '/api/company/rejectAddin'
      }
      vm.$vux.loading.show()
      vm.$post(url, {
        id: vm.info.id,
        user_id: userId
      }, (res) => {
        vm.$vux.loading.hide()
        if (res.success) {
          vm.getInfo(vm.info.id)
        } else {
          vm.$vux.toast.show({
            text: res.message,
            width: '13em'
          })
        }
      })
    },
    addin () {
      let vm = this
      vm.$vux.loading.show()
      vm.$post('/api/company/addin', {
        id: vm.info.id
      }, (res) => {
        if (res.success) {
          vm.$updateUser((res2) => {
            vm.$vux.loading.hide()
            if (res2.success) {
              vm.getUser()
              if (res.data === 1) {
                vm.$vux.alert.show({
                  title: '恭喜',
                  content: '您已加入 <strong> ' + vm.info.title + '</strong>',
                  onHide () {
                    vm.$router.back()
                  }
                })
              } else {
                vm.$vux.alert.show({
                  title: '提示',
                  content: '您已申请加入 <strong> ' + vm.info.title + '</strong>，请等待管理员审核。',
                  onHide () {
                    vm.$router.back()
                  }
                })
              }
            } else {
              vm.$vux.toast.show({
                text: res2.message,
                width: '13em'
              })
            }
          })
        } else {
          vm.$vux.loading.hide()
          vm.$vux.toast.show({
            text: res.message,
            width: '13em'
          })
        }
      })
    },
    quit () {
      let vm = this
      this.$vux.confirm.show({
        title: '退出企业',
        content: '确定要退出企业 <strong>' + vm.info.title + '</strong> 吗？',
        onConfirm () {
          vm.$vux.loading.show()
          vm.$post('/api/company/quit', {
            id: vm.info.id
          }, (res) => {
            vm.$vux.loading.hide()
            if (res.success) {
              if (res.data) {
                vm.setUser(res.data)
              }
              vm.$vux.alert.show({
                title: '提示',
                content: '您已退出 <strong> ' + vm.info.title + '</strong>。',
                onHide () {
                  vm.$router.back()
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
      })
    },
    invite () {
      let vm = this
      this.$vux.confirm.prompt('请输入对方手机号码', {
        title: '邀请新人加入',
        onConfirm (msg) {
          if (msg === '') {
            return
          } else if (/1[3456789]\d{9}/.test(msg) === false) {
            vm.$vux.toast.show({
              type: 'warn',
              text: '手机号码无效',
              width: '13em'
            })
            return
          }
          vm.$vux.loading.show()
          vm.$post('/api/company/invite', {
            id: vm.info.id,
            mobile: msg
          }, (res) => {
            vm.$vux.loading.hide()
            if (res.success) {
              vm.$vux.alert.show({
                title: '提示',
                content: '您向手机用户 <strong> ' + msg + '</strong> 发出邀请，请等待对方确认。'
              })
            } else {
              vm.$vux.toast.show({
                text: res.message,
                width: '16em'
              })
            }
          })
        }
      })
    },
    remove () {
      let vm = this
      this.$vux.confirm.show({
        title: '删除企业',
        content: '确定要删除企业 <strong>' + vm.info.title + '</strong> 吗？',
        onConfirm () {
          vm.$vux.loading.show()
          vm.$post('/api/company/remove', {
            id: vm.info.id
          }, (res) => {
            vm.$vux.loading.hide()
            if (res.success) {
              vm.$router.back()
            } else {
              vm.$vux.toast.show({
                text: res.message,
                width: '15em'
              })
            }
          })
        }
      })
    }
  }
}
</script>

<style lang="less">
  .company-info {
    padding:10px;
    text-align:center;
    h4 {
      margin-bottom: 20px;
      color:#999;
      text-align:center;
      white-space: nowrap;
      text-overflow: ellipsis;
    }
    img {
      height:60px;
    }
    p {
      color:#666;
    }
  }

  .wait-group {
    .weui-cells {
      border-top: 1px solid #e9e9e9;
    }
  }
</style>