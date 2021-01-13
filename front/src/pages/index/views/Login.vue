<template>
  <div style="margin:0 auto;max-width:600px;padding:15px;">

    <div style="margin:30px 0 30px 0;text-align:center;">
      <img src="/static/img/logo_s.png" style="width:120px;height:62px;">
      <h3 style="margin-top:10px;">商办云信息</h3>
    </div>

    <group>
      <x-input ref="inpLoginMobile" placeholder="输入手机号码" v-model="mobile" :max="11" :required="true"
         type="tel" is-type="china-mobile" @on-click-error-icon="mobileError" :should-toast-error="false" @on-enter="login" @on-change="validatedForm">
        <x-icon slot="label" type="iphone" size="28" style="fill:#333;position:relative;left:-6px;top:3px;"></x-icon>
      </x-input>
      <x-input v-if="loginModel !== 'verifyCode'" type="password" placeholder="输入登录密码" v-model="password" :max="16" :required="true"
        :should-toast-error="false" @on-click-error-icon="passwordError" @on-enter="login" @on-change="validatedForm">
        <x-icon slot="label" type="locked" size="20" style="fill:#333;position:relative;left:-2px;top:1px;margin-right:9px;"></x-icon>
      </x-input>
      <x-input v-if="loginModel !== 'verifyCode' && error" placeholder="输入验证码" v-model="vcode" :max="5" :required="true"
         :should-toast-error="false" @on-click-error-icon="vcodeError" @on-enter="login" @on-change="validatedForm">
        <img slot="right" id="imgVCode" class="weui-vcode-img" src="/api/verify" alt="captcha" @click="reloadVCodeImg">
      </x-input>
      <x-input v-if="loginModel === 'verifyCode'" placeholder="输入验证码" v-model="verifyCode" :max="6" :required="true"
        :should-toast-error="false" @on-click-error-icon="verifyCodeError" @on-enter="login" @on-change="validatedForm">
        <x-icon slot="label" type="android-apps" size="20" style="fill:#333;position:relative;left:-2px;top:1px;margin-right:9px;"></x-icon>
        <x-button slot="right" action-type="button" type="primary" mini :disabled="!isMobileValidated || timerStart" @click.native="sendVerifyCode">
          <span slot="default" v-show="!timerStart">发送验证码</span>
          <span solt="default" v-show="timerStart">
            <countdown v-model="timer" :start="timerStart" @on-finish="timerFinish"></countdown> 秒后重新发送
          </span>
        </x-button>
      </x-input>
    </group>
    <div>
      <p style="padding-top:10px;margin-bottom:30px;color:#0a95dc;">
        <a @click.prevent="changeLoginModel">
            <span v-show="loginModel === 'verifyCode'">使用账号密码登录</span>
            <span v-show="loginModel !== 'verifyCode'">使用验证码登录</span>
        </a>
      </p>

      <x-button action-type="button" @click.native="login" type="primary" :disabled="!formValidated">登录</x-button>
      <x-button action-type="button" @click.native="wechat" v-if="$isWechat()" type="primary" :plain="true">
        <x-icon type="wechat" class="btn-icon"></x-icon> 微信登录
      </x-button>
      <p v-if="error" style="margin-top:15px;color:red;text-align:center">{{message}}</p>
    </div>

    <qrcode></qrcode>
  </div>
</template>

<script>
import { Countdown } from 'vux'
import { mapState, mapActions } from 'vuex'
import qrcode from '@/components/qrcode.vue'

export default {
  components: {
    Countdown,
    qrcode
  },
  data () {
    return {
      formValidated: false,
      loginModel: 'verifyCode',
      mobile: '',
      password: '',
      vcode: '',
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
      if (localStorage.loginModel) {
        vm.loginModel = localStorage.loginModel
      }
    })
  },
  methods: {
    ...mapActions([
      'setUser'
    ]),
    validatedForm () {
      this.isMobileValidated = this.$refs.inpLoginMobile.valid
      this.formValidated = this.isMobileValidated &&
        ((this.loginModel !== 'verifyCode' &&
        this.password.length &&
        (this.vcode.length || this.error === false)) ||
        (this.loginModel === 'verifyCode' &&
        this.verifyCode.length))
    },
    changeLoginModel () {
      if (this.loginModel === 'verifyCode') {
        this.loginModel = 'password'
      } else {
        this.loginModel = 'verifyCode'
      }
      localStorage.loginModel = this.loginModel
    },
    mobileError () {
      this.$vux.toast.show({
        text: this.mobile.length ? '手机号码无效' : '请输入手机号'
      })
    },
    passwordError () {
      this.$vux.toast.show({
        text: '请输入密码'
      })
    },
    vcodeError () {
      this.$vux.toast.show({
        text: '请输入验证码'
      })
    },
    verifyCodeError () {
      this.$vux.toast.show({
        text: '请输入验证码'
      })
    },
    reloadVCodeImg () {
      document.getElementById('imgVCode').src = '/api/verify?' + (new Date()).valueOf()
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
    login () {
      this.validatedForm()
      if (!this.formValidated) {
        return
      }
      this.message = ''
      this.$vux.loading.show()
      let mobile = this.mobile.replace(/\s+/g, '')
      let pwd = ''
      let vcode = ''
      let verifyCode = ''
      if (this.loginModel === 'verifyCode') {
        verifyCode = this.verifyCode
      } else {
        pwd = this.password
        vcode = this.vcode
      }
      this.$login(mobile, pwd, vcode, verifyCode, (res) => {
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
          this.password = ''
          this.verifyCode = ''
          if (this.vcode.length) {
            this.reloadVCodeImg()
            this.vcode = ''
          }
          this.error = true
          this.message = res.message
        }
      })
    },
    wechat () {
      if (this.$isWechat()) {
        if (this.$route.query && this.$route.query.redirect) {
          window.location.href = '/api/wechat/login?redirect=' + encodeURI(this.$route.query.redirect)
        } else {
          window.location.href = '/api/wechat/login'
        }
      } else {

      }
    }
  },
  computed: {
    ...mapState({
      user: state => state.oice.user
    })
  }
}
</script>