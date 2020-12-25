// pages/company/member/member.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';

const app = getApp()

Page({

  /**
   * 页面的初始数据
   */
  data: {
    id: 0,
    pageIndex: 1,
    pageSize: 10,
    isLoading: false,
    isPullDown: false,
    isEnd: false,
    me: null,
    list: []
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let that = this
    if (options.id) {
      that.data.id = options.id
    }
    if (app.globalData.appUserInfo) {
      that.setData({
        me: app.globalData.appUserInfo
      })
      that.getList()
    } else {
      app.userLoginCallback = () => {
        that.setData({
          me: app.globalData.appUserInfo
        })
        that.getList()
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
      this.data.pageIndex = 1
      this.getList()
    }
    wx.stopPullDownRefresh()
  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
    if (this.data.isEnd == false) {
      this.data.pageIndex++
      this.getList()
    }
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  },

  getList: function() {
    let that = this

    if (that.data.isLoading) return

    that.setData({
      isLoading: true
    })

    if (that.data.pageIndex <= 1) {
      that.setData({
        list: []
      })
    }

    app.post('user/companyMember', { 
      id: that.data.id,
      page: that.data.pageIndex,
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
      that.setData({
        isPullDown: false,
        isLoading: false
      })
    })
  },

  turn: function(event) {
    let that = this
    const user = event.currentTarget.dataset.user
    Dialog.confirm({
      title: '转交确认',
      message: '确定要将管理权限转交给 ' + user.title + ' 吗？',
    })
    .then(() => {
      wx.showLoading()
      app.post('company/turn', {
        id: that.data.id,
        user_id: user.id
      }, (res) => {
        if (res.success) {
          that.getList()
          if (res.data) {
            app.globalData.appUserInfo = res.data
            that.setData({
              me: app.globalData.appUserInfo
            })
          }
          Dialog.alert({
            message: '转交成功'
          })
        } else {
          Dialog.alert({
            title: '发生错误',
            message: res.message ? res.message : '系统异常'
          })
        }
      }, () => {
        wx.hideLoading()
      })
    })
    .catch(() => {
    })
  },

  remove: function(event) {
    let that = this
    const user = event.currentTarget.dataset.user
    Dialog.confirm({
      title: '移除确认',
      message: '确定要确定要移除成员 ' + user.title + ' 吗？',
    })
    .then(() => {
      wx.showLoading()
      app.post('company/rejectAddin', {
        id: that.data.id,
        user_id: user.id
      }, (res) => {
        if (res.success) {
          that.getList()
        } else {
          Dialog.alert({
            title: '发生错误',
            message: res.message ? res.message : '系统异常'
          })
        }
      }, () => {
        wx.hideLoading()
      })
    })
    .catch(() => {
    })
  }
})