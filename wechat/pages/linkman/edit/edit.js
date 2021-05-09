// pages/customer/edit/edit.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';

const app = getApp()

Page({
  data: {
    id: 0,
    avatarFile: null,
    info: {
      __token__: '',
      type: '',          // 类别
      owner_id: 0,       // 客户ID
      title: '',         // 名称
      avatar: '',        // 头像
      department: '',    // 部门
      job: '',           // 职务
      mobile: '',        // 手机号码
      tel: '',           // 直线电话
      email: '',         // 电子邮箱
      weixin: '',        // 微信
      qq: '',            // QQ
      rem: '',           // 备注
      status: 0
    },
    is_title_empty: false,
    title_error: '',
    is_mobile_empty: false,
    mobile_empty: '',
    mobile_error: '',
    email_error: ''
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let that = this
    if (options.id) {
      that.data.id = options.id
      wx.setNavigationBarTitle({
        title: '修改联系人信息'
      })
    } else {
      wx.setNavigationBarTitle({
        title: '添加联系人'
      })
      if (options.type) {
        that.data.info.type = options.type
      }
      if (options.oid) {
        that.data.info.owner_id = options.oid
      }
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
    let url = 'linkman/edit?id=' + that.data.id
    app.get(url, (res) => {
      if (res.success && res.data) {
        let info = that.data.info
        for (let item in info) {
          if (res.data[item] !== undefined && res.data[item] !== null) {
            info[item] = res.data[item]
          }
        }
        that.setData({
          avatarFile: null,
          info: info
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

  onTitleInput: function(event) {
    this.setData({
      ['info.title']: event.detail
    })
    if (event.detail) {
      this.setData({
        is_title_empty: false,
        title_error: ''
      })
    }
  },

  onDepartmentInput: function(event) {
    this.setData({
      ['info.department']: event.detail
    })
  },

  onJobInput: function(event) {
    this.setData({
      ['info.job']: event.detail
    })
  },

  onMobileInput: function(event) {
    this.setData({
      ['info.mobile']: event.detail
    })
    if (event.detail) {
      this.setData({
        mobile_empty: ''
      })
      if (app.isMobile(event.detail)) {
        this.setData({
          is_mobile_empty: false,
          mobile_error: ''
        })
      }
    }
  },

  onTelInput: function(event) {
    this.setData({
      ['info.tel']: event.detail
    })
    if (event.detail) {
      this.setData({
        is_mobile_empty: false,
        mobile_empty: ''
      })
    }
  },

  onEmailInput: function(event) {
    this.setData({
      ['info.email']: event.detail
    })
    if (app.isEmail(event.detail)) {
      this.setData({
        is_email_error: false,
        email_error: ''
      })
    }
  },

  onWeixinInput: function(event) {
    this.setData({
      ['info.weixin']: event.detail
    })
  },

  onQQInput: function(event) {
    this.setData({
      ['info.qq']: event.detail
    })
  },

  onStatusChange: function(event) {
    this.setData({
      ['info.status']: event.detail ? 0 : 1
    })
  },

  onRemInput: function(event) {
    this.setData({
      ['info.rem']: event.detail
    })
  },

  getAvatar: function(event) {
    let that = this
    that.setData({
      avatarFile: event.detail.file,
      ['info.avatar']: event.detail.file.path
    })
  },

  upload: function() {
    let that = this
    try {
      let header = {
        'User-Token': app.globalData.appUserInfo && app.globalData.appUserInfo.token ? 
          app.globalData.appUserInfo.token : ''
      }
      if (!app.globalData.isWindows) {
        header['Content-Type'] =  'multipart/form-data'
      }

      wx.uploadFile({
        header: header,
        url: app.globalData.serverUrl + '/api/linkman/edit?id=' + that.data.id,
        filePath: that.data.avatarFile.url,
        name: 'file',
        formData: that.data.info,
        success(res) {
          if (res.data) {
            let json = JSON.parse(res.data)
            if (json.success) {
              if (that.data.info.type == 'building') {
                app.globalData.refreshBuildingView = true
              }
              if (that.data.info.type == 'unit') {
                app.globalData.refreshUnitView = true
              }
              if (that.data.info.type == 'customer') {
                app.globalData.refreshCustomerView = true
              }
              app.goBack()
            } else {
              if (json.data) {
                that.data.info.__token__ = json.data
              }
              if (json.message) {
                wx.showToast({
                  icon: 'none',
                  title: json.message,
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

  save: function() {
    let that = this
    app.post('linkman/edit?id=' + that.data.id, that.data.info, (res) => {
      if (res.success) {
        if (that.data.info.type == 'building') {
          app.globalData.refreshBuildingView = true
        }
        if (that.data.info.type == 'unit') {
          app.globalData.refreshUnitView = true
        }
        if (that.data.info.type == 'customer') {
          app.globalData.refreshCustomerView = true
        }
        app.goBack()
      } else if (res.message) {
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
    }, () => {
      wx.hideLoading()
    })
  },

  bindSave: function() {
    let that = this
    let error = 0
    if (!that.data.info.title) {
      error++
      that.setData({
        is_title_empty: true,
        title_error: '请输入姓名'
      })
    } else {
      that.setData({
        is_title_empty: false,
        title_error: ''
      })
    }

    if (!that.data.info.tel && !that.data.info.mobile) {
      error++
      that.setData({
        is_mobile_empty: true,
        mobile_empty: '手机号码直线电话和至少填写一项'
      })
    } else {
      that.setData({
        mobile_empty: ''
      })
      if (that.data.info.mobile && !app.isMobile(that.data.info.mobile)) {
        error++
        that.setData({
          is_mobile_empty: true,
          mobile_error: '请填写有效的手机号码'
        })
      } else {
        that.setData({
          is_mobile_empty: false,
          mobile_error: ''
        })
      }
    }

    if (that.data.info.email && !app.isEmail(that.data.info.email)) {
      error++
      that.setData({
        is_email_error: true,
        email_error: '请填写有效的电子信箱'
      })
    } else {
      that.setData({
        is_email_error: false,
        email_error: ''
      })
    }

    if (error > 0) {
      return
    }
    
    wx.showLoading({
      title: '保存中',
    })

    if (that.data.avatarFile) {
      that.upload()
    } else {
      that.save()
    }
  }
})