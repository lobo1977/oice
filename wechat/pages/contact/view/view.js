// pages/contact/view/view.js

//获取应用实例
const app = getApp()

Page({
  data: {
    isMe: false,
    isLoading: false,
    isPullDown: false,
    info: {
      id: 0,
      avatar: '',
      avatarView: '',
      title: '',       // 姓名
      mobile: '',      // 手机号码
      email: '',       // 电子邮箱
      weixin: '',      // 微信
      qq: '',          // QQ
      company_id: 0,
      company: '',     // 企业
      full_name: '',   // 企业全称
      logo: '',        // 企业logo
      in_contact: false,
      recommend: []
    }
  },
  
  onLoad(options) {
    let that = this;
    if (options.id) {
      that.data.info.id = options.id
    }
    if (options.key) {
      that.data.info.key = options.key
    }
    if (app.globalData.appUserInfo) {
      that.getView()
    } else {
      app.userLoginCallback = () => {
        that.getView()
      }
    }
  },
  
  onPullDownRefresh: function() {
    // 触发下拉刷新时执行
    if (this.data.isLoading == false) {
      this.data.isPullDown = true
      this.getView()
    }
    wx.stopPullDownRefresh()
  },
  
  // 转发事件
  onShareAppMessage(object) {
    let data = this.data.info
    let shareData = {
      title: data.title,
      path: '/pages/contact/view/view?id=' + data.id
    }
    if (data.logo) {
      shareData.imageUrl = app.serverUrl + '/' + data.logo
    }
    return shareData
  },
  
  bindShowRecommend(event) {
    let id = event.currentTarget.dataset.data.token
    wx.navigateTo({
      url: '../recommend/recommend?id=' + id
    })
  },
  
  bindPhoneCall(event) {
    let data = event.currentTarget.dataset.data
    if (data.mobile) {
      wx.makePhoneCall({
        phoneNumber: data.mobile
      })
    }
  },
  
  bindCopyWeixin(event) {
    let data = event.currentTarget.dataset.data
    wx.setClipboardData({
      data: data.weixin || data.mobile
    })
  },
  
  // 获取数据
  getView: function() {
    let that = this
    that.data.isLoading = true
    
    if (that.data.isPullDown == false) {
      wx.showLoading({
        title: '加载中',
      })
    } else {
      that.data.isPullDown = false
    }
    
    let url = 'user/detail?id=' + that.data.info.id
    if (that.data.info.key) {
      url += '&key=' + that.data.info.key
    }
    
    app.get(url, (res) => {
      if (res.data) {
        that.setData({
          info: res.data,
          isMe: app.globalData.appUserInfo.id == that.data.info.id
        })
      }
    }, () => {
      that.data.isLoading = false
      wx.hideLoading()
    })
  }
})