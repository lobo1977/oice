// pages/article/article.js

const app = getApp()

Page({

  /**
   * 页面的初始数据
   */
  data: {
    isLoading: false,
    isPullDown: false,
    info: {
      title: '',
      desc: '',
      cover: '',
      summary: '',
      content: ''
    }
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let that = this

    wx.showShareMenu({
      withShareTicket:true,
      menus:['shareAppMessage','shareTimeline']  
    })
    
    if (options.id) {
      that.data.info.id = options.id
    }

    if (app.globalData.appUserInfo) {
      this.getInfo()
    } else {
      app.userLoginCallback = () => {
        this.getInfo()
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

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {
    // 触发下拉刷新时执行
    if (this.data.isLoading == false) {
      this.data.isPullDown = true
      this.getInfo()
    }
    wx.stopPullDownRefresh()
  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
    let data = this.data.info
    let shareData = {
      title: data.title,
      query: '/pages/article/article?id=' + data.id
    }
    if (data.cover) {
      shareData.imageUrl = app.globalData.serverUrl + '/' + data.cover
    }
    return shareData
  },

  onShareTimeline: function() {
    let data = this.data.info
    let shareData = {
      title: data.title,
      query: 'id=' + data.id
    }
    if (data.cover) {
      shareData.imageUrl = app.globalData.serverUrl + '/' + data.cover
    }
    return shareData
  },

  getInfo: function() {
    let that = this
    that.data.isLoading = true

    if (that.data.isPullDown == false) {
      wx.showLoading({
        title: '加载中',
      })
    } else {
      that.data.isPullDown = false
    }
    
    let url = 'article/detail?id=' + that.data.info.id
    app.get(url, (res) => {
      if (res.data) {
        that.setData({
          info: res.data
        })
      }
    }, () => {
      that.data.isLoading = false
      wx.hideLoading()
    })
  }
})