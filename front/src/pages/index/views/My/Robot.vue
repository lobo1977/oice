<template>
  <div style="margin-top:15px;">
    <div style="text-align:center;">
      <divider>请使用微信扫码登录</divider>
      <div v-show="qrCode.length > 0">
        <qrcode :value="qrCode" type="img" style="margin:10px 0"></qrcode>
        <p style="margin-top:10px;font-size:0.8em;color:#999999;">
          以上二维码不支持长按识别，请分享二维码到其他终端显示，然后使用微信扫一扫功能。
          <br />机器人登成功录后，您的手机端微信会显示网页微信已登录。如果要保持机器人在线状态，请勿退出网页微信。
        </p>
      </div>
      <div style="margin-top:10px;text-align:center;">
        <x-button mini type="primary" @click.native="getQrCode" 
          :show-loading="loading" :disabled="loading">获取二维码</x-button>
      </div>
    </div>

    <actionsheet v-model="showAction" :menus="actions" theme="android" @on-click-menu="excuteAction"></actionsheet>

    <group-title v-show="robots.length > 0">在线机器人</group-title>
    <grid>
      <grid-item :cols="4" :label="robotStatus(item)" v-for="(item, i) in robots" :key="i" @click.native="robotAction(item)">
        <img slot="icon" :src="item.avatar">
      </grid-item>
    </grid>

    <popup v-model="showPush" position="bottom"
      height="100%" style="overflow-y:hidden;">
      <robotpush
        :is-shown="showPush"
        :robot="currentRobot"
        @on-close="closePush"></robotpush>
    </popup>
  </div>
</template>

<script>
import { InlineLoading, Qrcode, GroupTitle, Grid, GridItem } from 'vux'
import Robotpush from '@/components/RobotPush.vue'

const actions = {
  offline: '下线',
  sleep: '休眠',
  weakup: '唤醒',
  push: '群发消息'
}

export default {
  components: {
    InlineLoading,
    Qrcode,
    GroupTitle,
    Grid,
    GridItem,
    Robotpush
  },
  data () {
    return {
      actions,
      showAction: false,
      loading: false,
      qrCode: '',
      robots: [],
      timer: null,
      showPush: false,
      currentRobot: null
    }
  },
  mounted: function () {
    let vm = this
    vm.getOnlineRobots()
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
      this.currentRobot = null
      this.$get('/api/robot/online', (res) => {
        if (res.success) {
          this.robots = res.data
        }
      })
    },
    robotStatus (robot) {
      if (robot.status === 2) {
        return robot.name + '(休眠)'
      } else {
        return robot.name
      }
    },
    selectPush () {
      this.showPush = true
    },
    closePush () {
      this.showPush = false
    },
    robotAction (robot) {
      this.currentRobot = robot
      this.showAction = true
    },
    excuteAction (key, item) {
      this.showAction = false
      let vm = this
      if (vm.currentRobot == null) {
        return
      }
      if (key === 'push') {
        this.selectPush()
        return
      }
      vm.$vux.loading.show()
      vm.$post('/api/robot/' + key, {
        id: vm.currentRobot.id
      }, (res) => {
        vm.$vux.loading.hide()
        if (res.success) {
          vm.$vux.toast.show({
            type: 'success',
            text: '机器人已' + item + '。',
            width: '13em'
          })
        } else {
          vm.$vux.toast.show({
            text: res.message,
            width: '15em'
          })
        }
      })
    }
  }
}
</script>