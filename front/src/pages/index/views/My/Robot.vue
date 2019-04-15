<template>
  <div style="margin-top:15px;">
    <div style="text-align:center;">
      <divider>请使用微信扫码登录</divider>
      <inline-loading v-show="loading"></inline-loading>
      <qrcode v-show="qrCode.length > 0 && loading === false" :value="qrCode" type="img" style="margin:10px 0"></qrcode>
      <p style="margin-top:10px;font-size:0.8em;color:#999999;">
        以上二维码不支持长按识别，请分享二维码到其他终端显示，然后使用微信扫一扫功能。
      </p>
    </div>

    <group-title>在线机器人</group-title>
    <grid>
      <grid-item :cols="4" :label="item.name" v-for="(item, i) in robots" :key="i">
        <img slot="icon" :src="item.avatar">
      </grid-item>
    </grid>
  </div>
</template>

<script>
import { InlineLoading, Qrcode, Grid, GridItem, GroupTitle } from 'vux'

export default {
  components: {
    InlineLoading,
    Qrcode,
    Grid,
    GridItem,
    GroupTitle
  },
  data () {
    return {
      loading: false,
      qrCode: '',
      robots: [],
      timer: null
    }
  },
  mounted: function () {
    let vm = this
    vm.getQrCode()
    vm.timer = setInterval(() => {
      vm.getOnlineRobots()
    }, 30 * 1000)
  },
  beforeDestroy: function () {
    if (this.timer != null) {
      clearInterval(this.timer)
    }
  },
  methods: {
    getQrCode () {
      this.loading = true
      this.$get('/api/robot/qrcode', (res) => {
        this.loading = false
        if (res.success) {
          this.qrCode = res.data
        }
      })
    },
    getOnlineRobots () {
      this.$get('/api/robot/online', (res) => {
        if (res.success) {
          this.robots = res.data
        }
      })
    }
  }
}
</script>