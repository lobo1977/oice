// pages/company/edit/edit.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';

const app = getApp()

Page({

  /**
   * 页面的初始数据
   */
  data: {
    showArea: false,
    area: [],
    showJoinWay: false,
    join_way: [
      {name: '开放加入', value: 0}, 
      {name: '需管理员审核加入', value: 1}, 
      {name: '需通过邀请加入', value: 2}
    ],
    id: 0,
    info: {
      __token__: '',
      title: '',     // 简称
      full_name: '', // 全称
      area: '',      // 城区
      address: '',   // 地址
      rem: '',
      join_way: 0,
      status: 0
    },
    logo: '',
    logo_file: null,
    join_way_text: '',
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
        title: '修改企业信息'
      })
      that.data.id = options.id
    } else {
      wx.setNavigationBarTitle({
        title: '创建企业'
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

  onTitleInput: function(event) {
    this.data.info.title = event.detail
    if (this.data.info.title && this.data.is_title_empty) {
      this.setData({
        is_title_empty: false,
        title_error: ''
      })
    }
  },

  onFullNameInput: function(event) {
    this.data.info.full_name = event.detail
  },

  bindSelectArea: function() {
    this.setData({
      showArea: true
    })
  },

  onAreaPickerClose: function() {
    this.setData({
      showArea: false
    })
  },

  onAreaSelected: function(event) {
    this.setData({
      ['info.area']: event.detail.value,
      showArea: false
    })
  },

  onAddressInput: function(event) {
    this.data.info.address = event.detail
  },

  onRemInput: function(event) {
    this.data.info.rem = event.detail
  },

  bindSelectJoinWay: function() {
    this.setData({
      showJoinWay: true
    })
  },

  onJoinWayClose: function() {
    this.setData({
      showJoinWay: false
    })
  },

  onJoinWaySelect: function(event) {
    this.setData({
      ['info.join_way']: event.detail.value,
      join_way_text: event.detail.name,
      showJoinWay: false
    })
  },

  onStatusChange: function(event) {
    this.setData({
      ['info.status']: event.detail ? 1 : 0
    })
  },

  // 获取数据
  getInfo: function() {
    let that = this
    wx.showLoading({
      title: '加载中',
    })
    let url = 'company/edit?id=' + that.data.id
    app.get(url, (res) => {
      if (res.success && res.data) {
        let info = that.data.info
        for (let item in info) {
          if (res.data[item] !== undefined && res.data[item] !== null) {
            info[item] = res.data[item]
          }
        }

        let arrArea = []
        app.globalData.area.forEach(element => {
          if (element.id != 'all') {
            arrArea.push(element.text)
          }
        })

        that.setData({
          info: info,
          join_way_text: that.data.join_way[info.join_way].name,
          logo: res.data.logo ? res.data.logo : '',
          area: arrArea
        })
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

  setLogo: function(event) {
    this.data.logo_file = event.detail.file
    this.setData({
      logo: this.data.logo_file ? this.data.logo_file.path : ''
    })
  },

  uploadSave() {
    let that = this
    let header = {
      'User-Token': app.globalData.appUserInfo && app.globalData.appUserInfo.token ? 
        app.globalData.appUserInfo.token : ''
    }
    if (!app.globalData.isWindows) {
      header['Content-Type'] =  'multipart/form-data'
    }
    try {
      wx.uploadFile({
        header: header,
        url: app.globalData.serverUrl + '/api/company/edit?id=' + that.data.id,
        filePath: that.data.logo_file.path,
        name: 'logo',
        formData:  that.data.info,
        success(res) {
          if (res.data) {
            let json = JSON.parse(res.data)
            that.saveCallback(json)
          } else {
            wx.showToast({
              icon: 'none',
              title: '操作失败，系统异常',
              duration: 2000
            })
          }
        },
        complete() {
          wx.hideLoading()
        },
        fail(e) {
          wx.showToast({
            icon: 'none',
            title: '操作失败，系统异常',
            duration: 2000
          })
        }
      })
    } catch(e) {
      wx.hideLoading()
      Dialog.alert({
        title: '发生错误',
        message: e.message
      })
    }
  },

  saveCallBack: function(res) {
    if (res.success) {
      app.globalData.refreshCompany = true
      app.updateUserInfo()
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
  },
  
  save: function(event) {
    let that = this
    if (!that.data.info.title) {
      that.setData({
        is_title_empty: true,
        title_error: '请输入企业简称'
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

    if (that.data.logo_file) {
      that.uploadSave()
    } else {
      app.post('company/edit?id=' + that.data.id, that.data.info, (res) => {
        that.saveCallBack(res)
      }, () => {
        wx.hideLoading()
      })
    }
  }
})