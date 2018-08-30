<template>
  <div style="margin:0 auto;max-width:600px;padding:15px;">
    <group>
      <x-input ref="input_mobile" type="text" mask="999 9999 9999" placeholder="输入手机号码" v-model="mobile" :max="13" :required="true"
         is-type="china-mobile" @on-click-error-icon="mobileError" :should-toast-error="false" @on-enter="bind" @on-change="validatedForm">
        <x-icon slot="label" type="iphone" size="28" style="fill:#333;position:relative;left:-6px;top:3px;"></x-icon>
      </x-input>
      <x-input ref="input_verify_code" placeholder="输入验证码" v-model="verifyCode" :max="6" :required="true"
        :should-toast-error="false" @on-click-error-icon="verifyCodeError" @on-enter="bind" @on-change="validatedForm">
        <x-icon slot="label" type="android-apps" size="20" style="fill:#333;position:relative;left:-2px;top:1px;margin-right:9px;"></x-icon>
        <x-button slot="right" action-type="button" type="primary" mini :disabled="!isMobileValidated || timerStart" @click.native="sendVerifyCode">
          <span slot="default" v-show="!timerStart">发送验证码</span>
          <span solt="default" v-show="timerStart">
            <countdown v-model="timer" :start="timerStart" @on-finish="timerFinish"></countdown> 秒后重新发送
          </span>
        </x-button>
      </x-input>
    </group>
    <div style="margin-top:30px;">
      <x-button action-type="button" @click.native="bind" type="primary" :disabled="!formValidated">提交绑定</x-button>
      <p v-if="error" style="margin-top:15px;color:red;text-align:center">{{message}}</p>
    </div>
  </div>
</template>

<script>
import { Countdown } from 'vux'
import { mapState, mapActions } from 'vuex'

export default {
  components: {
    Countdown
  },
  data () {
    return {
      formValidated: false,
      mobile: '',
      verifyCode: '',
      isMobileValidated: false,
      timer: 60,
      timerStart: false,
      error: false,
      message: ''
    }
  },
  methods: {
    ...mapActions([
      'setUser'
    ]),
    validatedForm () {
      this.isMobileValidated = this.$refs.input_mobile.valid
      this.formValidated = this.isMobileValidated && this.verifyCode.length
    },
    mobileError () {
      this.$vux.toast.show({
        text: this.mobile.length ? '手机号码无效' : '请输入手机号'
      })
    },
    verifyCodeError () {
      this.$vux.toast.show({
        text: '请输入验证码'
      })
    },
    reloadVCodeImg () {
      document.getElementById('imgVCode').src = '/verify?' + (new Date()).valueOf()
    },
    sendVerifyCode () {
      this.message = ''
      this.timerStart = true
      this.$sendVerifyCode(this.mobile.replace(/\s+/g, ''), (res) => {
        if (!res.success) {
          this.timerStart = false
          this.$vux.toast.show({
            text: res.message,
            width: '16em'
          })
        }
      })
    },
    timerFinish () {
      this.timerStart = false
    },
    bind () {
      this.validatedForm()
      if (!this.formValidated) {
        return
      }
      this.message = ''
      this.$vux.loading.show()
      this.$mobile(this.mobile.replace(/\s+/g, ''), this.verifyCode, (res) => {
        this.$vux.loading.hide()
        if (res.success) {
          this.error = false
          if (res.data) {
            this.setUser(res.data)
          }
          if (this.$route.query && this.$route.query.redirect) {
            this.$router.replace({path: this.$route.query.redirect})
          } else {
            this.$router.push({name: 'My'})
          }
        } else {
          this.verifyCode = ''
          this.error = true
          this.message = res.message
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