<template>
  <div>
    <form id="frmMy">
      <input type="hidden" name="__token__" v-model="info.__token__">
      <group gutter="0" label-width="4em" label-margin-right="1em" label-align="right">
        <cell title="头像">
          <div solt="default" style="height:60px;line-height:0;">
            <img v-show="avatarSrc != null && avatarSrc != ''" :src="avatarSrc" style="width:60px;height:60px;">
          </div>
          <input id="inputAvatar" type="file" name="avatar" class="upload" @change="loadAvatar"
              accept="image/*">
        </cell>
        <x-input name="title" ref="input_name" title="姓名" v-model="info.title" :required="true" :max="30"
          @on-click-error-icon="nameError" :should-toast-error="false" @on-change="validateForm"></x-input>
        <cell title="手机" :value="info.mobile" is-link value-align="left" :link="{name: 'ChangeMobile'}"></cell>
        <x-input name="email" type="email" ref="input_email" title="电子邮箱" v-model="info.email" :max="30" is-type="email"
          @on-change="validateForm" @on-click-error-icon="emailError" :should-toast-error="false"></x-input>
        <x-input name="weixin" title="微信" v-model="info.weixin" :max="30"></x-input>
        <x-input name="qq" title="QQ" v-model="info.qq" :max="30"></x-input>
      </group>
    </form>

    <div class="bottom-bar">
      <x-button type="primary" class="bottom-btn" @click.native="save" :disabled="!formValidate">
        <x-icon type="android-archive" class="btn-icon"></x-icon> 保存
      </x-button>
    </div>
  </div>
</template>

<script>
import { mapState, mapActions } from 'vuex'

export default {
  components: {
  },
  data () {
    return {
      formValidate: false,
      info: {
        __token__: '',
        title: '',    // 名称
        mobile: '',   // 手机号码
        email: '',    // 电子邮箱
        weixin: '',   // 微信
        qq: ''       // QQ
      },
      avatarSrc: null
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      let user = vm.$store.state.oice.user
      vm.info.title = user.title
      vm.info.mobile = user.mobile
      vm.info.email = user.email
      vm.info.weixin = user.weixin
      vm.info.qq = user.qq
      vm.avatarSrc = user.avatar
      vm.$get('/api/index/token', (res) => {
        if (res.success) {
          vm.info.__token__ = res.data
        } else {
          vm.$vux.toast.show({
            text: res.message,
            width: '15em'
          })
        }
      })
    })
  },
  methods: {
    ...mapActions([
      'setUser'
    ]),
    nameError () {
      this.$vux.toast.show({
        text: '请输入姓名'
      })
    },
    emailError () {
      this.$vux.toast.show({
        text: '电子邮箱无效'
      })
    },
    validateForm () {
      this.formValidate = this.$refs.input_name.valid &&
          this.$refs.input_email.valid
    },
    loadAvatar () {
      let src = document.getElementById('inputAvatar')
      if (!src.files || !src.files[0]) {
        return
      }
      let reader = new FileReader()
      reader.onload = (e) => {
        this.avatarSrc = e.target.result
      }
      reader.readAsDataURL(src.files[0])
    },
    save () {
      this.validateForm()
      if (!this.formValidate) {
        return
      }
      this.$vux.loading.show()
      this.$postFile('/api/my/edit', '#frmMy', (res) => {
        this.$vux.loading.hide()
        if (res.success) {
          if (res.data) {
            this.setUser(res.data)
          }
          this.$router.back()
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
    ...mapState({
      user: state => state.oice.user
    })
  }
}
</script>

<style lang="less">
.upload {
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height: 100%;
    opacity:0;
  }
</style>