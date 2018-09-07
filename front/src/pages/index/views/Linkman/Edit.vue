<template>
  <div>
    <group gutter="0" label-width="4em" label-margin-right="1em" label-align="right">
      <x-input ref="inpLinkmanTitle" title="姓名" v-model="info.title" :required="true" :max="10"
        @on-click-error-icon="titleError" :should-toast-error="false" @on-change="validateForm"></x-input>
      <x-input title="所在部门" v-model="info.department" :max="20"></x-input>
      <x-input title="职务" v-model="info.job" :max="20"></x-input>
      <x-input ref="inpLinkmanMobile" title="手机号码" type="number"
        v-model="info.mobile" :max="11" :required="true" is-type="china-mobile"
        @on-change="validateForm" @on-click-error-icon="mobileError" :should-toast-error="false"></x-input>
      <x-input title="办公电话" v-model="info.tel" :max="20"></x-input>
      <x-input ref="inpLinkmanEmail" type="email" title="电子邮箱" v-model="info.email" :max="30" is-type="email"
        @on-change="validateForm" @on-click-error-icon="emailError" :should-toast-error="false"></x-input>
      <x-input title="微信" v-model="info.weixin" :max="30"></x-input>
      <x-input title="QQ" v-model="info.qq" :max="30"></x-input>
      <x-switch title="是否在职" v-model="info.status"></x-switch>
    </group>

    <group gutter="10px">
      <x-textarea placeholder="备注" :rows="3" v-model="info.rem" :max="200"></x-textarea>
    </group>

    <div class="bottom-bar">
      <x-button type="primary" class="bottom-btn" @click.native="save" :disabled="!formValidate">
        <x-icon type="android-archive" class="btn-icon"></x-icon> 保存
      </x-button>
    </div>
  </div>
</template>

<script>
export default {
  components: {
  },
  data () {
    return {
      id: 0,
      formValidate: false,
      info: {
        __token__: '',
        type: '',          // 类别
        owner_id: 0,       // 客户ID
        title: '',         // 名称
        department: '',    // 部门
        job: '',           // 职务
        mobile: '',        // 手机号码
        tel: '',           // 办公电话
        email: '',         // 电子邮箱
        weixin: '',        // 微信
        qq: '',            // QQ
        rem: '',           // 备注
        status: true
      }
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      if (to.params.id) {
        vm.id = parseInt(to.params.id)
        if (isNaN(vm.id)) {
          vm.id = 0
        }
      }

      if (to.params.type) {
        vm.info.type = to.params.type
      }

      if (to.params.oid) {
        vm.info.owner_id = parseInt(to.params.oid)
        if (isNaN(vm.info.owner_id)) {
          vm.info.owner_id = 0
        }
      }

      vm.$get('/api/linkman/edit?id=' + vm.id, (res) => {
        if (res.success) {
          if (vm.id) {
            for (let item in vm.info) {
              if (res.data[item] !== undefined && res.data[item] !== null) {
                vm.info[item] = res.data[item]
              }
            }
            vm.info.status = vm.info.share === 0
            vm.$emit('on-view-loaded', vm.info.title)
          } else {
            vm.info.__token__ = res.data.__token__
            vm.$emit('on-view-loaded', '添加联系人')
          }
        } else {
          vm.$vux.toast.show({
            text: res.message,
            width: '16em'
          })
        }
      })
    })
  },
  methods: {
    titleError () {
      this.$vux.toast.show({
        text: '请输入联系人姓名'
      })
    },
    mobileError () {
      this.$vux.toast.show({
        text: this.info.mobile.length ? '手机号码无效' : '请输入手机号码'
      })
    },
    emailError () {
      this.$vux.toast.show({
        text: '电子邮箱无效'
      })
    },
    validateForm () {
      this.formValidate = this.$refs.inpLinkmanTitle.valid &&
        this.$refs.inpLinkmanMobile.valid && this.$refs.inpLinkmanEmail.valid
    },
    save () {
      this.validateForm()
      if (!this.formValidate) {
        return
      }
      this.$vux.loading.show()
      this.info.status = (this.info.status ? 0 : 1)
      this.$post('/api/linkman/edit?id=' + this.id, this.info, (res) => {
        this.$vux.loading.hide()
        if (res.success) {
          if (this.id === 0) {
            this.id = res.data
          }
          this.$router.back()
        } else {
          this.info.__token__ = res.data
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

<style lang="less">
</style>