<template>
  <div>
    <masker color="255,255,255" :opacity="1" style="margin:10px;height:140px;border-radius:2px;">
      <div slot="content" class="user-info" @click="eidt">
        <div style="height:60px;">
          <h4 @click.stop="goCompany">{{user.company}}</h4>
        </div>
        <img v-if="user.avatar && user.avatar.length > 0" :src="user.avatar" />
        <h2>{{user.title}}</h2>
        <p>{{user.mobile}}</p>
      </div>
    </masker>
    <group gutter="0">
      <cell title="我的收藏" :link="{name:'Favorite'}">
        <x-icon slot="icon" type="android-star" class="cell-icon"></x-icon>
      </cell>
      <cell v-if="user.superior_id > 0" title="我的上级" 
        :link="{name:'UserView', params: {id: user.superior_id}}"
        :value="user.superior">
        <x-icon slot="icon" type="android-contact" class="cell-icon"></x-icon>
      </cell>
      <cell title="我的企业" :link="{name:'Company'}" :value="user.company">
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
import { Masker, Group, Cell } from 'vux'

export default {
  components: {
    Masker,
    Group,
    Cell
  },
  data () {
    return {
      user: {
        id: 0,
        type: 0,
        title: '',
        avatar: '',
        company_id: 0,
        company: '',
        logo: '',
        mobile: '',
        email: '',
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
    goCompany () {
      this.$router.push({name: 'CompanyView', params: {id: this.user.company_id}})
    },
    eidt () {
      this.$router.push({name: 'Info'})
    }
  }
}
</script>

<style lang="less">
  .user-info {
    padding:10px;
    h4 {
      width:70%;
      white-space: nowrap;
      text-overflow: ellipsis;
      color:#999;
    }
    h2 {
      white-space: nowrap;
      text-overflow: ellipsis;
      color:#999;
    }
    img {
      position: absolute;
      top:10px;
      right:10px;
      width:60px;
      height:60px;
      border-radius: 5px;
    }
    p {
      white-space: nowrap;
      text-overflow: ellipsis;
      color:#666;
    }
  }
</style>