// pages/customer/index.js

const app = getApp()

Page({

  data: {
    keyword: '',
    type: 'follow',
    pageIndex: 1,
    pageSize: 10,
    isLoading: false,
    isPullDown: false,
    isEnd: false,
    list: []
  },

  onKewordChange(e) {
    this.data.keyword = e.detail
  },

  // 搜索客户
  bindSearch: function(event) {
    this.data.pageIndex = 1
    this.setData({
      type: ''
    })
    this.getList()
  },

  onCancel: function() {
    this.setData({
      keyword: '',
      type: ''
    })
    this.data.pageIndex = 1
    this.getList()
  },

  onTabChange(event) {
    this.data.pageIndex = 1
    this.data.keyword = ''
    this.setData({
      type: event.detail.name
    })
    this.getList()
  },

  getList: function() {
    let that = this
    that.setData({
      isLoading: true
    })
    if (that.data.pageIndex <= 1) {
      that.setData({
        list: []
      })
    }
    app.post('customer/index', { 
      page: that.data.pageIndex,
      keyword: that.data.keyword,
      type: that.data.type
    }, (res) => {
      if (!res.data || res.data.length < that.data.pageSize) {
        that.setData({
          isEnd: true
        })
      } else {
        that.setData({
          isEnd: false
        })
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
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    if (app.globalData.appUserInfo) {
      this.getList()
    } else {
      app.userLoginCallback = () => {
        this.getList()
      }
    }
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    if (app.globalData.refreshCustomer) {
      this.getList()
      app.globalData.refreshCustomer = false
    }
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {
  },

  onPullDownRefresh: function() {
    // 触发下拉刷新时执行
    if (this.data.isLoading == false) {
      this.setData({
        isPullDown: true
      })
      this.data.pageIndex = 1
      this.getList()
    }
    wx.stopPullDownRefresh()
  },

  onReachBottom: function() {
    if (this.data.isEnd == false) {
      this.data.pageIndex++
      this.getList();
    }
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
  },

  bindAdd: function() {
    app.checkUser('/pages/customer/edit/edit')
  }
})