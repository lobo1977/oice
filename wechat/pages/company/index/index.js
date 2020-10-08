// pages/company/index/index.js
const app = getApp()

Page({

  /**
   * 页面的初始数据
   */
  data: {
    isLoading: false,
    isPullDown: false,
    me: null,
    my: [],
    waites: [],
    inviteMe: [],
    creates: [],
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    if (app.globalData.appUserInfo) {
      this.setData({
        me: app.globalData.appUserInfo
      })
      this.getList()
    } else {
      app.userLoginCallback = () => {
        this.setData({
          me: app.globalData.appUserInfo
        })
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

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {
    // 触发下拉刷新时执行
    if (this.data.isLoading == false) {
      this.setData({
        isPullDown: true
      })
      this.getList()
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

  },

  getList: function() {
    let that = this
    that.setData({
      isLoading: true
    })
    
    app.get('company', (res) => {
      if (res.data) {
        if (res.data.my) {
          that.setData({
            my: res.data.my
          })
        } else {
          that.setData({
            my: []
          })
        }
        if (res.data.myWait) {
          that.setData({
            waites: res.data.myWait
          })
        } else {
          that.setData({
            waites: []
          })
        }
        if (res.data.inviteMe) {
          that.setData({
            inviteMe: res.data.inviteMe
          })
        } else {
          that.setData({
            inviteMe: []
          })
        }
        if (res.data.myCreate) {
          that.setData({
            creates: res.data.myCreate
          })
        } else {
          that.setData({
            creates: []
          })
        }
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