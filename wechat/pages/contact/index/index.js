//index.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog'

//获取应用实例
const app = getApp()

Page({
  data: {
    pageIndex: 1,
    pageSize: 10,
    isLoading: false,
    isPullDown: false,
    isEnd: false,
    me: null,
    list: []
  },

  onLoad: function (options) {
    let that = this

    wx.showShareMenu({
      withShareTicket:true,
      menus:['shareAppMessage','shareTimeline']  
    })
    
    wx.showLoading({
      title: '加载中',
    })
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
      this.setData({
        isPullDown: true
      })
      this.data.pageIndex = 1
      this.getList()
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

  onShareTimeline: function() {
    let data = this.data.info
    let shareData = {
      title: '【商办云】联系人',
    }
    return shareData
  },

  // 获取列表
  getList: function () {
    let that = this
    that.data.isLoading = true
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

  remove: function(event) {
    let that = this
    const user = event.currentTarget.dataset.user
    Dialog.confirm({
      title: '移除确认',
      message: '确定要确定要移除联系人 ' + user.title + ' 吗？',
    })
    .then(() => {
      wx.showLoading()
      app.post('my/removeContact', {
        contact_id: user.id
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