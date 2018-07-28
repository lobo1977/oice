<template>
  <div>
    <group gutter="0" label-width="4em" label-margin-right="1em" label-align="right">
      <cell title="姓名" value-align="left" :value="info.title" v-if="info.title"></cell>
      <cell title="部门" value-align="left" :value="info.department" v-if="info.department"></cell>
      <cell title="职务" value-align="left" :value="info.job" v-if="info.job"></cell>
      <cell title="手机号码" value-align="left" :value="info.mobile" v-if="info.mobile"></cell>
      <cell title="办公电话" value-align="left" :value="info.tel" v-if="info.tel"></cell>
      <cell title="电子邮箱" value-align="left" :value="info.email" v-if="info.email"></cell>
      <cell title="微信" value-align="left" :value="info.weixn" v-if="info.weixin"></cell>
      <cell title="QQ" value-align="left" :value="info.qq" v-if="info.qq"></cell>
      <cell title="状态" value-align="left" :value="info.status"></cell>
    </group>

    <group title="备注" v-show="info.rem.length > 0">
      <p class="group-padding">{{ info.rem }}</p>
    </group>

    <flexbox :gutter="0" class="bottom-bar">
      <flexbox-item :span="6">
        <x-button type="default" class="bottom-btn" 
          :disabled="info.id === 0 || (info.user_id > 0 && info.user_id != user.id)"
          :link="{name:'LinkmanEdit', params: {id: info.id, type: info.type, oid: info.owner_id}}">
          <x-icon type="compose" class="btn-icon"></x-icon> 修改
        </x-button>
      </flexbox-item>
      <flexbox-item>
        <x-button type="warn" class="bottom-btn" @click.native="remove"
          :disabled="info.id === 0 || (info.user_id > 0 && info.user_id != user.id)">
          <x-icon type="trash-a" class="btn-icon"></x-icon> 删除
        </x-button>
      </flexbox-item>
    </flexbox>
  </div>
</template>

<script>
import { Group, Cell, Flexbox, FlexboxItem, XButton } from 'vux'

export default {
  components: {
    Group,
    Cell,
    Flexbox,
    FlexboxItem,
    XButton
  },
  data () {
    return {
      user: null,
      info: {
        id: 0,
        type: '',
        owner_id: 0,
        title: '',          // 姓名
        department: '',     // 部门
        job: '',            // 职务
        mobile: '',         // 手机号码
        tel: '',            // 办公电话
        email: '',          // 电子邮箱
        weixin: '',         // 微信
        qq: '',             // QQ
        rem: '',            // 备注
        status: '',         // 状态
        user_id: 0
      }
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      vm.user = vm.$store.state.oice.user || vm.user
      let id = parseInt(to.params.id)
      if (!isNaN(id)) {
        vm.$get('/api/linkman/detail?id=' + id, (res) => {
          if (res.success) {
            for (let item in vm.info) {
              if (res.data[item] !== undefined && res.data[item] !== null) {
                vm.info[item] = res.data[item]
              }
            }
            vm.info.status = (vm.info.status === 0 ? '在职' : '离职')
            vm.$emit('on-view-loaded', vm.info.title)

            if (vm.$isWechat()) {
              let shareLink = window.location.href
              let shareDesc = vm.info.department + vm.info.job
              let shareImage = window.location.protocol + '//' +
                window.location.host + '/static/img/logo.png'

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
        vm.$emit('on-view-loaded', '联系人信息')
      }
    })
  },
  methods: {
    remove () {
      if (this.user) {
        let vm = this
        this.$vux.confirm.show({
          title: '删除联系人',
          content: '确定要删除联系人 <strong>' + vm.info.title + '</strong> 吗？',
          onConfirm () {
            vm.$vux.loading.show({text: '请稍后...'})
            vm.$post('/api/linkman/remove', {
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
      } else {
        this.$router.push({
          name: 'Login',
          query: { redirect: this.$route.fullPath }
        })
      }
    }
  }
}
</script>

<style lang="less">
</style>