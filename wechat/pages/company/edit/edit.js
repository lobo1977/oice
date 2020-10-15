// pages/company/edit/edit.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';

const app = getApp()

Page({

  /**
   * 页面的初始数据
   */
  data: {
    info: {
      __token__: '',
      id: 0,
      title: '',     // 简称
      full_name: '', // 全称
      area: '',      // 城区
      address: '',   // 地址
      rem: '',
      join_way: 0,
      status: 0,
      bool_status: false
      //enable_stamp: 0,
      //bool_enable_stamp: true
    },
    logo: '',
    is_title_empty : false,
    title_error: ''
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
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

  onTitleInput: function(event) {
    this.data.info.title = event.detail
    if (this.data.info.title && this.data.is_title_empty) {
      that.setData({
        is_title_empty: false,
        title_error: ''
      })
    }
  }
})