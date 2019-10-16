<template>
  <div>
    <div class="card">
      <img :src="info.logo" alt="" class="logo">
      <h4 class="name">{{info.title}}</h4>
      <p class="company">{{info.full_name}}</p>
      <div class="contact">
        <p><x-icon type="iphone" size="18" style="position:relative;top:3px;left:-2px"></x-icon>{{info.mobile}}</p>
        <p v-if="info.email"><x-icon type="android-mail" size="14" style="position:relative;top:3px;"></x-icon> {{info.email}}</p>
        <p v-if="info.weixin"><x-icon type="wechat" size="16" style="position:relative;top:3px;"></x-icon> {{info.weixin}}</p>
      </div>
    </div>

    <div v-if="user.id != info.id && user.company_id != info.company_id" class="bottom-bar">
      <x-button v-if="!info.in_contact" type="primary" class="bottom-btn" @click.native="saveToContact" :disabled="info.id <= 0">
        <x-icon type="android-contact" class="btn-icon"></x-icon> 保存到通讯录
      </x-button>
      <x-button v-if="info.in_contact" type="primary" class="bottom-btn" @click.native="removeContact" :disabled="info.id <= 0">
        <x-icon type="android-contact" class="btn-icon"></x-icon> 从通讯录移除
      </x-button>
    </div>

    <!-- <group>
      <cell :title="info.title" :inline-desc="info.full_name">
        <img slot="icon" :src="info.avatar" class="cell-image" 
          style="width:4em;margin-right:1em;max-height:4em;">
      </cell>
    </group>

    <group gutter="10px" label-width="4em" label-margin-right="1em" label-align="right">
      <cell value-align="left" v-if="info.mobile">
        <x-icon slot="icon" type="iphone" class="cell-icon"></x-icon>
        <a v-bind:href="'tel:'+(info.mobile)" class="cell-link">{{info.mobile}}</a>
      </cell>
      <cell value-align="left" :value="info.email" v-if="info.email">
        <x-icon slot="icon" type="android-mail" class="cell-icon"></x-icon>
      </cell>
      <cell value-align="left" :value="info.weixn" v-if="info.weixin">
        <x-icon slot="icon" type="wechat" class="cell-icon"></x-icon>
      </cell>
      <cell title="QQ" value-align="left" :value="info.qq" v-if="info.qq"></cell>
    </group> -->
  </div>
</template>

<script>
export default {
  directives: {
  },
  components: {
  },
  data () {
    return {
      user: {
        id: 0,
        company_id: 0
      },
      info: {
        id: 0,
        avatar: '',
        avatarView: '',
        title: '',       // 姓名
        mobile: '',      // 手机号码
        email: '',       // 电子邮箱
        weixin: '',      // 微信
        qq: '',          // QQ
        company_id: 0,
        company: '',     // 企业
        full_name: '',   // 企业全称
        logo: '',        // 企业logo
        in_contact: false
      }
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      vm.user = vm.$store.state.oice.user || vm.user
      let id = parseInt(to.params.id)
      if (!isNaN(id)) {
        vm.$get('/api/user/detail?id=' + id, (res) => {
          if (res.success) {
            for (let item in vm.info) {
              if (res.data[item] !== undefined && res.data[item] !== null) {
                vm.info[item] = res.data[item]
              }
            }

            if (vm.$isWechat()) {
              let shareLink = window.location.href
              let shareDesc = vm.info.full_name
              let shareImage = window.location.protocol + '//' +
                window.location.host + vm.info.logo

              vm.$wechatShare(null, shareLink, vm.info.title + '的名片', shareDesc, shareImage)
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
    saveToContact () {
      let vm = this
      vm.$vux.loading.show()
      vm.$post('/api/my/addContact', {
        contact_id: vm.info.id
      }, (res) => {
        vm.$vux.loading.hide()
        if (res.success) {
          vm.info.in_contact = true
          vm.$vux.toast.show({
            type: 'success',
            text: '已添加到通讯录。',
            width: '13em'
          })
        } else {
          vm.$vux.toast.show({
            text: res.message,
            width: '13em'
          })
        }
      })
    },
    removeContact () {
      let vm = this
      this.$vux.confirm.show({
        title: '移除联系人',
        content: '确定要移除联系人 <strong>' + vm.info.title + '</strong> 吗？',
        onConfirm () {
          vm.$vux.loading.show()
          vm.$post('/api/my/removeContact', {
            contact_id: vm.info.id
          }, (res) => {
            vm.$vux.loading.hide()
            if (res.success) {
              vm.info.in_contact = false
              vm.$vux.toast.show({
                type: 'success',
                text: '已从通讯录移除。',
                width: '13em'
              })
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
  }
}
</script>

<style lang="less">
  .card {
    margin:10px 5px;
    border:1px solid #e0e0e0;
    background-color: #f5f5f5;
    padding:10px;
    border-radius: 3px;
    .logo {
      width: 100px;
      float: right;
    }
    .name { font-size: 1.5em; }
    .company { margin-bottom: 60px; font-size:0.90em;}
    .contact {
      font-size:0.90em;
    }
  }
</style>