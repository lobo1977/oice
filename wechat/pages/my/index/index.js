// pages/my/index/index.js

const app = getApp()

Page({

  /**
   * 页面的初始数据
   */
  data: {
    me: null
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    if (app.globalData.appUserInfo) {
      this.setData({
        me: app.globalData.appUserInfo
      })
    } else {
      app.userLoginCallback = () => {
        this.setData({
          me: app.globalData.appUserInfo
        })
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
    this.setData({
      me: app.globalData.appUserInfo
    })
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
    this.setData({
      me: app.globalData.appUserInfo
    })
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

  onChat: function () {
    if (wx.openCustomerServiceChat) {
      wx.openCustomerServiceChat({
        extInfo: { url: 'https://work.weixin.qq.com/kfid/kfc0beff0aaf2fd8d4a' },
        corpId: 'ww3d426954268a1aee',
        success(res) {}
      })
    } else {
      wx.navigateTo({
        url: '../contact/contact',
      })
    }
  }
})