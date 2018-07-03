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

    // 隐藏分享按钮
    Vue.wechatHideShare = function (url) {
      Vue.post('/api/wechat/config', {
        url: url || window.location.href
      }, (res) => {
        if (res.success && res.data) {
          res.data.jsApiList = ['hideMenuItems']
          Vue.wechat.config(res.data)

          Vue.wechat.error((res) => {})

          Vue.wechat.ready(() => {
            Vue.wechat.hideMenuItems({
              menuList: [
                'menuItem:share:appMessage',
                'menuItem:share:timeline',
                'menuItem:share:qq',
                'menuItem:share:QZone'
              ]
            })
          })
        }
      })
    }

    // 注册微信分享接口
    Vue.wechatShare = function (url, link, title, desc, image) {
      Vue.post('/api/wechat/config', {
        url: url || window.location.href
      }, (res) => {
        if (res.success && res.data) {
          res.data.jsApiList = ['showMenuItems',
            'onMenuShareTimeline', 'onMenuShareAppMessage', 'onMenuShareQQ']

          Vue.wechat.config(res.data)

          Vue.wechat.error((res) => {})

          Vue.wechat.ready(() => {
            Vue.wechat.showMenuItems({
              menuList: [
                'menuItem:share:appMessage',
                'menuItem:share:timeline',
                'menuItem:share:qq',
                'menuItem:share:QZone'
              ]
            })

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

            Vue.wechat.onMenuShareQZone({
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

    Vue.prototype.$wechatShare = (url, link, title, desc, image) => {
      Vue.wechatShare(url, link, title, desc, image)
    }

    Vue.prototype.$wechatHideShare = (url) => {
      Vue.wechatHideShare(url)
    }
  }
}
