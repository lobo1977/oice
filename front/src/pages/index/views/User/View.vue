<template>
  <div>
    <group>
      <cell :title="info.title" :inline-desc="info.company">
        <img slot="icon" :src="info.avatar" class="cell-image" 
          style="width:4em;margin-right:1em"
          @click.stop="previewAvatar">
      </cell>
    </group>

    <div v-transfer-dom>
      <x-dialog v-model="showAvatar" hide-on-blur class="avatar-view">
        <img :src="info.avatarView" style="max-width:100%" 
          @click="showAvatar = false"></x-dialog>
    </div>

    <group gutter="10px" label-width="4em" label-margin-right="1em" label-align="right">
      <cell title="手机号码" value-align="left" :value="info.mobile" v-if="info.mobile"></cell>
      <cell title="电子邮箱" value-align="left" :value="info.email" v-if="info.email"></cell>
      <cell title="微信" value-align="left" :value="info.weixn" v-if="info.weixin"></cell>
      <cell title="QQ" value-align="left" :value="info.qq" v-if="info.qq"></cell>
    </group>

    <div class="bottom-bar">
      <x-button v-if="info.canSetSuperior" type="primary" class="bottom-btn" @click.native="setSuperior">
        <x-icon type="android-contact" class="btn-icon"></x-icon> 指定为我的上级
      </x-button>
    </div>

    <flexbox :gutter="0" class="bottom-bar">
      <flexbox-item :span="6">
      </flexbox-item>
      <flexbox-item>
      </flexbox-item>
    </flexbox>
  </div>
</template>

<script>
import { Group, Cell, Flexbox, FlexboxItem, XButton, TransferDom, XDialog } from 'vux'
import { mapActions } from 'vuex'

export default {
  directives: {
    TransferDom
  },
  components: {
    Group,
    Cell,
    Flexbox,
    FlexboxItem,
    XButton,
    XDialog
  },
  data () {
    return {
      info: {
        id: 0,
        avatar: '',
        avatarView: '',
        title: '',       // 姓名
        mobile: '',      // 手机号码
        email: '',       // 电子邮箱
        weixin: '',      // 微信
        qq: '',          // QQ
        company: '',
        isSuperior: false,
        canSetSuperior: false
      },
      showAvatar: false
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      let id = parseInt(to.params.id)
      if (!isNaN(id)) {
        vm.$get('/api/user/detail?id=' + id, (res) => {
          if (res.success) {
            for (let item in vm.info) {
              if (res.data[item] !== undefined && res.data[item] !== null) {
                vm.info[item] = res.data[item]
              }
            }

            if (vm.info.avatar) {
              vm.info.avatarView = vm.info.avatar.replace('/60/', '/200/')
            }

            if (vm.$isWechat()) {
              let shareLink = window.location.href
              let shareDesc = '商办云经纪人'
              let shareImage = window.location.protocol + '//' +
                window.location.host + vm.info.avatar

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
      }
    })
  },
  methods: {
    ...mapActions([
      'setUser'
    ]),
    previewAvatar () {
      if (this.info.avatarView) {
        this.showAvatar = true
      }
    },
    setSuperior () {
      let vm = this
      vm.$vux.loading.show()
      vm.$post('/api/company/setSuperior', {
        user_id: vm.info.id
      }, (res) => {
        vm.$vux.loading.hide()
        if (res.success) {
          if (res.data) {
            vm.setUser(res.data)
            vm.info.isSuperior = true
            vm.info.canSetSuperior = false
            vm.$vux.alert.show({
              title: '提示',
              content: '您已将 <strong> ' + vm.info.title + '</strong> 指定为上级，您的客户资料将对其可见。'
            })
          }
        } else {
          this.info.__token__ = res.data
          this.$vux.toast.show({
            text: res.message,
            width: '15em'
          })
        }
      })
    }
  },
  computed: {
  }
}
</script>

<style lang="less">
.avatar-view .weui-dialog {
  background-color:transparent;
  border-radius:0;
}
</style>