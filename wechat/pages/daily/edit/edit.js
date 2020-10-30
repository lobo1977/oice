// pages/daily/edit/edit.js
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
      level: 0,
      title: '', 
      summary: '',
      start_time: app.formatTime(Date.now(), 'yyyy-MM-dd HH:mm'),
      end_time: ''
    },
    start_time: Date.now(),
    showStartTime: false,
    end_time: '',
    showEndTime: false,
    is_title_empty : false,
    title_error: ''
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let that = this
    if (options.id) {
      wx.setNavigationBarTitle({
        title: '修改工作日报'
      })
      that.data.id = options.id
    } else {
      wx.setNavigationBarTitle({
        title: '添加工作日报'
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

  selectStartTime: function() {
    this.setData({
      showStartTime: true
    })
  },

  onStartTimeConfirm: function(event) {
    this.setData({
      start_time: event.detail,
      ['info.start_time']: app.formatTime(event.detail, 'yyyy-MM-dd HH:mm'),
      showStartTime: false
    })
  },

  onStartTimeClose: function() {
    this.setData({
      showStartTime: false
    })
  },

  onStartTimeCancel: function() {
    this.setData({
      showStartTime: false
    })
  },

  selectEndTime: function() {
    this.setData({
      showEndTime: true
    })
  },

  onEndTimeConfirm: function(event) {
    this.setData({
      end_time: event.detail,
      ['info.end_time']: app.formatTime(this.data.start_time, 'yyyy-MM-dd') + ' ' + event.detail,
      showEndTime: false
    })
  },

  onEndTimeClose: function() {
    this.setData({
      showEndTime: false
    })
  },

  onEndTimeCancel: function() {
    this.setData({
      showEndTime: false
    })
  },

  onTitleInput: function(event) {
    this.data.info.title = event.detail
    if (this.data.info.title && this.data.is_title_empty) {
      this.setData({
        is_title_empty: false,
        title_error: ''
      })
    }
  },

  onSummaryInput: function(event) {
    this.data.info.summary = event.detail
  },

  // 获取数据
  getInfo: function() {
    let that = this
    wx.showLoading({
      title: '加载中',
    })
    let url = 'daily/edit?id=' + that.data.id
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
          start_time: Date.now(),
          end_time: '',
        })

        if (res.data.start_time) {
          let time = Date.parse(res.data.start_time.replace(/-/g, '/'))
          that.setData({
            start_time: time,
            ['info.start_time']: app.formatTime(time,'yyyy-MM-dd HH:mm')
          })
        }

        if (res.data.end_time) {
          let time = Date.parse(res.data.end_time.replace(/-/g, '/'))
          that.setData({
            end_time: app.formatTime(time,'HH:mm')
          })
        }

      } else {
        Dialog.alert({
          title: '发生错误',
          message: res.message ? res.message : '系统异常'
        }).then(() => {
          wx.navigateBack()
        })
      }
    }, () => {
      wx.hideLoading()
    })
  },
  
  save: function(event) {
    let that = this
    if (!that.data.info.title) {
      that.setData({
        is_title_empty: true,
        title_error: '请输入摘要'
      })
      return
    } else if (that.data.is_title_empty) {
      that.setData({
        is_title_empty: false,
        title_error: ''
      })
    }
    
    wx.showLoading({
      title: '保存中',
    })

    app.post('daily/edit?id=' + that.data.id, that.data.info, (res) => {
      if (res.success) {
        app.globalData.refreshDaily = true
        wx.navigateBack()
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