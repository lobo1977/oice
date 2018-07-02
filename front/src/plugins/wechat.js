import Vue from 'vue'
import HttpRequest from './http'
import { WechatPlugin } from 'vux'

Vue.use(HttpRequest)
Vue.use(WechatPlugin)

export default {
  install (Vue, options) {
    // 判读是否是微信客户端
    Vue.isWechat = function () {
      let ua = navigator.userAgent.toLowerCase()
      return /micromessenger/.test(ua)
    }

    // 微信接口注册
    Vue.wechatConfig = function (link, title, desc, image) {
      Vue.post('/api/wechat/config', {
        url: window.location.href
      }, (res) => {
        if (res.success) {
          Vue.wechat.config(res.data)

          Vue.wechat.error((res) => {
          })

          Vue.wechat.ready(() => {
            Vue.wechat.onMenuShareTimeline({
              title: title,
              link: link,
              imgUrl: image
            })

            Vue.wechat.onMenuShareAppMessage({
              title: title,
              desc: desc,
              link: link,
              imgUrl: image
            })

            Vue.wechat.onMenuShareQQ({
              title: title,
              desc: desc,
              link: link,
              imgUrl: image
            })
          })
        }
      })
    }

    Vue.prototype.$isWechat = () => {
      return Vue.isWechat()
    }

    Vue.prototype.$wechatConfig = (link, title, desc, image) => {
      Vue.wechatConfig(link, title, desc, image)
    }
  }
}
