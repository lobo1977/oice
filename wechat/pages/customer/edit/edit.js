// pages/customer/edit/edit.js

const app = getApp()

Page({

  /**
   * 页面的初始数据
   */
  data: {
    id: 0,
    info: {
      __token__: '',
      customer_name: '',    // 名称
      tel: '',              // 直线电话
      area: '',             // 城区
      address: '',          // 地址
      demand: '',           // 需求项目
      lease_buy: '',        // 租赁/购买
      district: '',         // 意向商圈
      min_acreage: null,    // 最小面积(平方米)
      max_acreage: null,    // 最大面积(平方米)
      budget: '',           // 预算
      settle_date: '',      // 入驻日期
      current_area: null,   // 在驻面积
      end_date: '',         // 到期日
      remind: 8,            // 到期提醒（提前月份数）
      rem: '',              // 项目说明
      status: 6,            // 状态
      company_id: 0,        // 所属企业
      share: 0,
      boolShare: false,     // 共享状态
      linkman: '',          // 联系人
      clash: 0              // 撞单客户
    },
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let that = this
    if (options.id) {
      that.data.id = options.id
    }
    if (options.oid) {
      that.data.owner_id = options.oid
    }
    if (app.globalData.appUserInfo) {
      that.getData()
    } else {
      app.userLoginCallback = () => {
        that.getData()
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

  // 获取数据
  getData: function() {
    let that = this
    wx.showLoading({
      title: '加载中',
    })

    let url = 'customer/log?id=' + that.data.id
    app.get(url, (res) => {
      if (res.data) {
        that.setData({
          __token__: res.data.__token__
        })
        if (res.data.start_time) {
          that.setData({
            start_time: Date.parse(res.data.start_time.replace(/-/g, '/'))
          })
        }
        if (res.data.owner_id) {
          that.setData({
            owner_id: res.data.owner_id
          })
        }
        if (res.data.title) {
          that.setData({
            title: res.data.title
          })
        }
        if (res.data.summary) {
          that.setData({
            summary: res.data.summary
          })
        }
      }
    }, () => {
      wx.hideLoading()
    })
  },

  bindSave: function() {
    let that = this
    if (!that.data.title) {
      that.setData({
        is_title_empty: true,
        title_error: '请输入摘要'
      })
    } else {
      that.setData({
        is_title_empty: false,
        title_error: ''
      })

      wx.showLoading({
        title: '保存中',
      })

      app.post('customer/log?id=' + that.data.id, {
        __token__: that.data.__token__,
        owner_id: that.data.owner_id,
        start_time: app.formatTime(new Date(that.data.start_time), 'yyyy-MM-dd HH:mm:ss'),
        title: that.data.title,
        summary: that.data.summary
      }, (res) => {
        if (res.success) {
          wx.navigateBack()
        } else if (res.message) {
          wx.showToast({
            icon: 'none',
            title: res.message,
            duration: 2000
          })
        }
      }, () => {
        wx.hideLoading()
      })
    }
  }
})