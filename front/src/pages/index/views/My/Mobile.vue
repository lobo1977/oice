<template>
  <div style="margin:0 auto;max-width:600px;padding:15px;">
    <group>
      <x-input ref="inpMyMobile" type="number" placeholder="输入手机号码" v-model="mobile" :max="11" :required="true"
         is-type="china-mobile" @on-click-error-icon="mobileError" :should-toast-error="false" @on-enter="confirm" @on-change="validatedForm">
        <x-icon slot="label" type="iphone" size="28" style="fill:#333;position:relative;left:-6px;top:3px;"></x-icon>
      </x-input>
      <x-input placeholder="输入验证码" v-model="verifyCode" :max="6" :required="true"
        :should-toast-error="false" @on-click-error-icon="verifyCodeError" @on-enter="confirm" @on-change="validatedForm">
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
      <x-button action-type="button" @click.native="confirm" type="primary" :disabled="!formValidated">提交</x-button>
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
  beforeRouteEnter (to, from, next) {
    next(vm => {
    })
  },
  methods: {
    ...mapActions([
      'setUser'
    ]),
    validatedForm () {
      this.isMobileValidated = this.$refs.inpMyMobile.valid
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
    confirm () {
      this.validatedForm()
      if (!this.formValidated) {
        return
      }
      this.message = ''
      this.$vux.loading.show()
      this.$post('/api/my/mobile', {
        mobile: this.mobile.replace(/\s+/g, ''),
        verifyCode: this.verifyCode
      }, (res) => {
        this.$vux.loading.hide()
        if (res.success) {
          this.error = false
          if (res.data) {
            this.setUser(res.data)
          }
          this.$router.back()
        } else {
          this.password = ''
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