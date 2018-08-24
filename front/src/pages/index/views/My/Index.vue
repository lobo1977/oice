<template>
  <div>
    <group gutter="0">
      <cell :title="user.title" :inline-desc="user.mobile"
        :link="{name: 'Info'}">
        <img slot="icon" :src="user.avatar" class="cell-image">
      </cell>
    </group>

    <grid :show-lr-borders="false" :show-vertical-dividers="false">
      <grid-item :link="{name:'Favorite'}" label="收藏">
        <x-icon slot="icon" size="30" type="android-star" style="fill:rgb(4, 190, 2);"></x-icon>
      </grid-item>
      <grid-item :link="{name:'MyBuilding'}" label="项目">
        <x-icon slot="icon" size="30" type="home" style="fill:rgb(253, 237, 16);"></x-icon>
      </grid-item>
      <grid-item :link="{name:'MyCustomer'}" label="到期客户">
        <x-icon slot="icon" size="30" type="android-calendar" style="fill:rgb(245, 78, 1);"></x-icon>
      </grid-item>
    </grid>

    <group gutter="0.5em">
      <cell title="我的企业" :link="{name:'Company'}" :value="user.company">
        <x-icon slot="icon" type="android-people" class="cell-icon"></x-icon>
      </cell>
      <cell v-if="user.superior_id > 0" title="我的上级" 
        :link="{name:'UserView', params: {id: user.superior_id}}"
        :value="user.superior">
        <x-icon slot="icon" type="android-contact" class="cell-icon"></x-icon>
      </cell>
      <cell v-if="user.company_id > 0" title="企业成员" :link="{name: 'CompanyMember', params: {id: user.company_id}}">
        <x-icon slot="icon" type="android-people" class="cell-icon"></x-icon>
      </cell>
      <cell title="修改密码" :link="{name:'Password'}">
        <x-icon slot="icon" type="android-lock" class="cell-icon"></x-icon>
      </cell>
      <cell title="退出登录" :link="{name:'Logout'}">
        <x-icon slot="icon" type="android-exit" class="cell-icon"></x-icon>
      </cell>
    </group>
  </div>
</template>

<script>
import { Group, Cell, Grid, GridItem } from 'vux'

export default {
  components: {
    Group,
    Cell,
    Grid,
    GridItem
  },
  data () {
    return {
      user: {
        // id: 0,
        // type: 0,
        title: '',
        avatar: '',
        company_id: 0,
        company: '',
        // logo: '',
        mobile: '',
        // email: '',
        superior_id: 0,
        superior: ''
      }
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      vm.user = vm.$store.state.oice.user || vm.user
    })
  },
  methods: {
  }
}
</script>

<style lang="less">
  .weui-grids {
    margin-top:0.5em;
    background-color: #fff;
    .weui-grid {
      padding:15px 10px;
    }
  }
</style>