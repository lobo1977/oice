<template>
  <div>
    <group gutter="0">
      <cell
        v-if="user.id"
        :title="user.title"
        :inline-desc="user.company"
        :link="{ name: 'Info' }">
        <img slot="icon" :src="user.avatar" class="cell-image" />
      </cell>
      <cell v-if="!user.id" title="登录" :is-link="true" @click.native="login">
        <img slot="icon" src="/static/img/avatar.png" class="cell-image" />
      </cell>
    </group>

    <grid :show-lr-borders="false" :show-vertical-dividers="false">
      <grid-item :link="{ name: 'Favorite' }" label="收藏">
        <x-icon
          slot="icon"
          size="30"
          type="android-star"
          style="fill: rgb(4, 190, 2)"></x-icon>
      </grid-item>
      <grid-item :link="{ name: 'MyBuilding' }" label="项目">
        <x-icon
          slot="icon"
          size="30"
          type="home"
          style="fill: rgb(253, 237, 16)"></x-icon>
      </grid-item>
      <grid-item :link="{ name: 'MyCustomer' }" label="到期客户">
        <x-icon
          slot="icon"
          size="30"
          type="android-calendar"
          style="fill: rgb(245, 78, 1)"></x-icon>
      </grid-item>
    </grid>

    <group gutter="0.5em">
      <cell title="我的企业" :link="{ name: 'Company' }">
        <x-icon slot="icon" type="cube" class="cell-icon"></x-icon>
        <div style="display: inline-block">
          <span style="vertical-align: middle">{{ user.company }}</span>
          <badge v-if="user.invite_me"></badge>
        </div>
      </cell>
      <cell
        v-if="user.superior_id > 0"
        title="我的上级"
        :link="{ name: 'UserView', params: { id: user.superior_id } }"
        :value="user.superior">
        <x-icon slot="icon" type="android-contact" class="cell-icon"></x-icon>
      </cell>
      <cell
        v-if="user.id"
        title="我的名片"
        :link="{ name: 'UserCard', params: { id: user.id } }">
        <x-icon slot="icon" type="filing" class="cell-icon"></x-icon>
      </cell>
      <cell v-if="user.id" title="通讯录" :link="{ name: 'Contact' }">
        <x-icon slot="icon" type="android-contacts" class="cell-icon"></x-icon>
      </cell>
      <cell
        v-if="user.id && user.type < 10"
        title="微信机器人"
        :link="{ name: 'Robot' }">
        <x-icon slot="icon" type="wechat" class="cell-icon"></x-icon>
      </cell>
      <cell
        v-if="user.id && user.isAdmin"
        title="文章管理"
        :link="{ name: 'Article' }">
        <x-icon slot="icon" type="document-text" class="cell-icon"></x-icon>
      </cell>
      <cell v-if="user.id" title="修改密码" :link="{ name: 'Password' }">
        <x-icon slot="icon" type="android-lock" class="cell-icon"></x-icon>
      </cell>
      <cell v-if="user.id" title="退出登录" :link="{ name: 'Logout' }">
        <x-icon slot="icon" type="android-exit" class="cell-icon"></x-icon>
      </cell>
    </group>

    <service></service>
  </div>
</template>

<script>
import { Grid, GridItem } from 'vux'

export default {
  components: {
    Grid,
    GridItem
  },
  data () {
    return {
      user: {
        // id: 0,
        type: 0,
        title: '',
        avatar: '',
        company_id: 0,
        company: '',
        // logo: '',
        mobile: '',
        // email: '',
        superior_id: 0,
        superior: '',
        invite_me: 0
      }
    }
  },
  beforeRouteEnter (to, from, next) {
    next((vm) => {
      vm.user = vm.$store.state.oice.user || vm.user
    })
  },
  methods: {
    login () {
      this.$checkAuth()
    }
  }
}
</script>

<style lang="less">
.weui-grids {
  margin-top: 0.5em;
  background-color: #fff;
  .weui-grid {
    padding: 15px 10px;
  }
}
</style>