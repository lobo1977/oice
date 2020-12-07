// pages/daily/review/review.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';

const app = getApp()

Page({
  /**
   * 页面的初始数据
   */
  data: {
    id: 0,
    info: {
      __token__: '',
      level: 1,
      review_user: 0,
      review_date: '',
      content: ''
    },
    level_text: '',
    showLevel: false,
    level: [{name: '不合格', value: 0},{name: '合格', value: 1},{name: '优秀', value: 2}],
    is_content_empty: false
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let that = this
    if (options.id) {
      wx.setNavigationBarTitle({
        title: '修改批阅'
      })
      that.data.id = options.id
    } else {
      if (options.user) {
        that.data.info.review_user = options.user
      }
      if (options.date) {
        that.data.info.review_date = options.date
      }
      wx.setNavigationBarTitle({
        title: '批阅工作日报'
      })
    }
    if (app.globalData.appUserInfo) {
      that.getInfo()
    } else {
      app.userLoginCallback = () => {
        that.getInfo()
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

  onContentInput: function(event) {
    this.data.info.content = event.detail
    if (this.data.info.content && this.data.is_content_empty) {
      this.setData({
        is_content_empty: false
      })
    }
  },

  selectLevel: function() {
    this.setData({
      showLevel: true
    })
  },

  onLevelClose: function() {
    this.setData({
      showLevel: false
    })
  },

  onLevelSelect: function(event) {
    this.setData({
      ['info.level']: event.detail.value,
      level_text: event.detail.name,
      showLevel: false
    })
  },

  // 获取数据
  getInfo: function() {
    let that = this
    wx.showLoading({
      title: '加载中',
    })
    let url = 'daily/editReview?id=' + that.data.id
    app.get(url, (res) => {
      if (res.success && res.data) {
        let info = that.data.info
        for (let item in info) {
          if (res.data[item] !== undefined && res.data[item] !== null) {
            info[item] = res.data[item]
          }
        }
        that.setData({
          info: info,
          level_text: that.data.level[info.level].name,
        })
      } else {
        Dialog.alert({
          title: '发生错误',
          message: res.message ? res.message : '系统异常'
        }).then(() => {
          app.goBack()
        })
      }
    }, () => {
      wx.hideLoading()
    })
  },
  
  save: function(event) {
    let that = this
    if (!that.data.info.content) {
      that.setData({
        is_content_empty: true
      })
      return
    } else if (that.data.is_content_empty) {
      that.setData({
        is_content_empty: false
      })
    }
    
    wx.showLoading({
      title: '保存中',
    })

    app.post('daily/editReview?id=' + that.data.id, that.data.info, (res) => {
      if (res.success) {
        app.globalData.refreshDaily = true
        app.goBack()
      } else {
        if (res.data) {
          that.data.info.__token__ = json.data
        }
        if (res.message) {
          wx.showToast({
            icon: 'none',
            title: res.message,
            duration: 2000
          })
        } else {
          wx.showToast({
            icon: 'none',
            title: '操作失败，系统异常',
            duration: 2000
          })
        }
      }
    }, () => {
      wx.hideLoading()
    })
  }
})