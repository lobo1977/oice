// pages/contact/recommend.js
//获取应用实例
const app = getApp()

Page({
  data: {
    isLoading: false,
    isPullDown: false,
    id: 0,
    customer: {
      title: '',
      customer_name: ''
    },
    company: {
      full_name: ''
    },
    manager: {
      title: '',
      mobile: ''
    },
    date: '',
    list: []
  },
  
  onLoad(options) {
    let that = this;
    if (options.id) {
      that.data.id = options.id
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
    this.data.isPullDown = false
  },
  
  // 转发事件
  onShareAppMessage(object) {
    let data = this.data
    let shareData = {
      title: '【商办云】项目推荐',
      path: '/page/contact/recommend/recommend?id=' + data.id
      // shareData.imageUrl
    }
    return shareData
  },

  bindViewBuilding(event) {
    let id = event.currentTarget.dataset.data.building_id
    wx.navigateTo({
      url: '../../building/view/view?id=' + id
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
    
    let url = 'customer/show?id=' + that.data.id
    app.get(url, (res) => {
      if (res.data) {
        that.setData({
          customer: res.data.customer,
          company: res.data.company,
          manager: res.data.manager,
          date: res.data.date,
          list: res.data.list
        })
      }
    }, () => {
      that.data.isLoading = false
      wx.hideLoading()
    })
  }
})