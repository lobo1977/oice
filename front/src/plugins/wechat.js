import Vue from 'vue'
import wx from 'weixin-js-sdk'
import HttpRequest from './http'

Vue.use(HttpRequest)

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
          wx.config(res.data)

          wx.error((res) => {})

          wx.ready(() => {
            wx.hideMenuItems({
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
          res.data.jsApiList = [ 'showMenuItems',
            'updateAppMessageShareData',
            'updateTimelineShareData',
            'getLocation',
            'openLocation',
            'previewImage']

          wx.config(res.data)

          wx.error((res) => {})

          wx.ready(() => {
            wx.showMenuItems({
              menuList: [
                'menuItem:share:appMessage',
                'menuItem:share:timeline',
                'menuItem:share:qq',
                'menuItem:share:QZone'
              ]
            })

            wx.updateAppMessageShareData({
              title: title,
              desc: desc,
              link: link,
              imgUrl: image
            })

            wx.updateTimelineShareData({
              title: title,
              desc: desc,
              link: link,
              imgUrl: image
            })
          })
        }
      })
    }

    Vue.getLocation = function (cb) {
      wx.getLocation({
        type: 'wgs84', // 默认为wgs84的gps坐标，如果要返回火星坐标，可传入'gcj02'
        success: function (res) {
          // res.latitude 纬度，浮点数，范围为90 ~ -90
          // res.longitude 经度，浮点数，范围为180 ~ -180。
          // res.speed 速度，以米/每秒计
          // res.accuracy 位置精度
          if (cb) {
            cb(res)
          }
        }
      })
    }

    Vue.previewImage = function (current, urls) {
      wx.previewImage({
        current: current, // 当前显示图片的http链接
        urls: urls, // 需要预览的图片http链接列表
        fail: (res) => {
          // alert(res.errMsg)
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

    Vue.prototype.$getLocation = (cb) => {
      return Vue.getLocation(cb)
    }

    Vue.prototype.$previewImage = (current, urls) => {
      Vue.previewImage(current, urls)
    }
  }
}
