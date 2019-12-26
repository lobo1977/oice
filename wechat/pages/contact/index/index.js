//index.js

//获取应用实例
const app = getApp()

Page({
  data: {
    pageIndex: 1,
    pageSize: 10,
    isLoading: false,
    isPullDown: false,
    isEnd: false,
    list: []
  },
  onLoad: function (options) {
    wx.showLoading({
      title: '加载中',
    })
    if (app.globalData.appUserInfo) {
      this.getList()
    } else {
      app.userLoginCallback = () => {
        this.getList()
      }
    }
  },
  onShow: function () {
  },
  onReady: function () {
    // 页面首次渲染完毕时执行
  },
  onHide: function () {
    // 页面从前台变为后台时执行
  },
  onUnload: function () {
    // 页面销毁时执行
  },
  onPullDownRefresh: function () {
    // 触发下拉刷新时执行
    if (this.data.isLoading == false) {
      this.isPullDown = true
      this.data.pageIndex = 1
      this.getList()
    } else {
      this.isPullDown = false
    }
    wx.stopPullDownRefresh()
  },
  onReachBottom: function () {
    if (this.data.isEnd == false) {
      this.data.pageIndex++
      this.getList();
    }
  },
  onShareAppMessage: function () {
    let shareData = {
      title: '【商办云】联系人',
      path: '/pages/contact/index/index'
    }
    return shareData
  },
  //事件处理函数
  bindView: function (event) {
    let id = event.currentTarget.dataset.data.id
    wx.navigateTo({
      url: '../view/view?id=' + id
    })
  },

  // 获取列表
  getList: function () {
    let that = this
    that.setData({
      isLoading: true
    })
    if (that.data.pageIndex <= 1) {
      that.setData({
        list: []
      })
    }
    app.post('my/contact', {
      page: that.data.pageIndex,
      only_my: 1
    }, (res) => {
      if (!res.data || res.data.length < that.data.pageSize) {
        that.data.isEnd = true
      }
      if (res.data) {
        let list = that.data.list.concat(res.data)
        that.setData({
          list: list
        })
      }
    }, () => {
      wx.hideLoading()
      that.setData({
        isPullDown: false,
        isLoading: false
      })
    })
  }
})