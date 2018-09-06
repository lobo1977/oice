<template>
  <form @submit.prevent="changePwd" autocomplete="off" 
    style="margin:0 auto;max-width:600px;padding:15px;">

    <group>
      <x-input ref="inpChangePwd" type="password" placeholder="输入密码" v-model="password" :max="16" :required="true"
        :should-toast-error="false" @on-click-error-icon="passwordError" @on-change="validateform">
      </x-input>
      <x-input ref="inpRepetPwd" type="password" placeholder="再次输入密码" v-model="repetPassword" :max="16"
        :equal-with="password" :should-toast-error="false" @on-click-error-icon="repetPasswordError" @on-change="validateform">
      </x-input>
    </group>

    <group>
      <x-button action-type="submit" type="primary" :disabled="!formValid">确定</x-button>
    </group>
  </form>
</template>

<script>
export default {
  components: {
  },
  data () {
    return {
      password: '',
      repetPassword: '',
      formValid: false
    }
  },
  methods: {
    passwordError () {
      this.$vux.toast.show({
        text: '请输入密码'
      })
    },
    repetPasswordError () {
      this.$vux.toast.show({
        text: '两次输密码不一致',
        width: '14em'
      })
    },
    validateform () {
      this.formValid = this.$refs.inpChangePwd.valid &&
        this.$refs.inpRepetPwd.valid && this.password === this.repetPassword
    },
    changePwd () {
      this.$vux.loading.show()
      this.$post('/api/my/changePwd', {
        password: this.password
      }, (res) => {
        this.$vux.loading.hide()
        if (res.success) {
          const _this = this
          this.$vux.toast.show({
            type: 'success',
            text: '密码修改成功',
            onHide () {
              if (window.history.length) {
                _this.$router.back()
              } else {
                _this.$router.push('/my')
              }
            }
          })
        } else {
          this.$vux.toast.show({
            text: res.message,
            width: '16em'
          })
        }
      })
    }
  }
}
</script>