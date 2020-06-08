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
    this.setData({
      keyword: e.detail
    });
  },

  // 搜索客户
  bindSearch: function(event) {
    this.setData({
      pageIndex: 1,
      type: '',
    });
    this.getList();
  },

  onTabChange(event) {
    this.setData({
      pageIndex: 1,
      type: event.detail.name,
      keyword: ''
    });
    this.getList();
  },

  getList: function() {
    let that = this
    that.setData({
      isLoading: true
    })
    if (that.data.isPullDown === false) {
      wx.showLoading({
        title: '加载中',
      })
    }
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
        that.data.isEnd = true
      } else {
        that.data.isEnd = false
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

  bindViewCustomer: function(event) {
    let id = event.currentTarget.dataset.data.id
    wx.navigateTo({
      url: '../view/view?id=' + id
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
      this.data.isPullDown = true
      this.data.pageIndex = 1
      this.getList()
    } else {
      this.data.isPullDown = false
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

  }
})