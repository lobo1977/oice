<template>
  <div style="margin-top:15px;">
    <div style="text-align:center;">
      <divider>请使用微信扫码登录</divider>
      <div v-show="qrCode.length > 0">
        <qrcode :value="qrCode" type="img" style="margin:10px 0"></qrcode>
        <p style="margin-top:10px;font-size:0.8em;color:#999999;">
          以上二维码不支持长按识别及从本机相册识别，请截图发送到其他手机或电脑，然后用微信扫一扫功能。
          <br />您需要先关注公众微信号“商办云”，扫码后两分钟公众号回复：“签到成功”，机器人方可正常显示。
          <br />机器人登录成功后，您的手机端微信会显示网页微信已登录。如果要保持机器人在线状态，请勿退出网页微信。
        </p>
      </div>
      <div style="margin-top:10px;text-align:center;">
        <x-button mini type="primary" @click.native="getQrCode" 
          :show-loading="loading" :disabled="loading">获取二维码</x-button>
      </div>
    </div>

    <actionsheet v-model="showAction" :menus="actions" theme="android" @on-click-menu="excuteAction"></actionsheet>

    <group :gutter="0" title="在线机器人">
      <cell v-for="(item, index) in robots" :key="index" :title="robotStatus(item)"
        @click.native="robotAction(item)">
        <img slot="icon" :src="item.avatar" class="cell-image">
        <p slot="inline-desc" class="cell-desc">待处理任务：{{item.task}} 个</p>
      </cell>
    </group>

    <popup v-model="showPush" position="bottom"
      height="100%" style="overflow-y:hidden;">
      <robotpush
        :is-shown="showPush"
        :robot="currentRobot"
        content=""
        @on-close="closePush"></robotpush>
    </popup>
  </div>
</template>

<script>
import { InlineLoading, Qrcode } from 'vux'
import Robotpush from '@/components/RobotPush.vue'

const actions = {
  offline: '下线',
  sleep: '休眠',
  weakup: '唤醒',
  // push: '群发消息',
  clearTask: '删除任务'
}

export default {
  components: {
    InlineLoading,
    Qrcode,
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
    }, 10 * 1000)
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
            text: key !== 'clearTask' ? (item + '指令已发送成功') : '已删除所有任务',
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