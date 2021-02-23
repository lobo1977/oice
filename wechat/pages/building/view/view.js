//view.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';

//获取应用实例
const app = getApp()

Page({
  data: {
    me: null,
    isLoading: false,
    isPullDown: false,
    isVideoPlay: false,
    info: {
      id: 0,
      building_name: '',    // 名称
      building_type: '',    // 类型
      area: '',             // 城区
      district: '',         // 商圈
      address: '',          // 地址
      longitude: 0,         // 经度
      latitude: 0,          // 纬度
      subway: '',           // 地铁
      completion_date_text: '',  // 竣工日期
      rent_sell: '',        // 租售
      price: '',            // 价格
      acreage: 0,           // 建筑面积
      floor: '',            // 楼层
      floor_area: 0,        // 层面积
      floor_height: 0,      // 层高
      bearing: 0,           // 楼板承重
      developer: '',        // 开发商
      manager: '',          // 物业管理
      fee: '',              // 物业费
      electricity_fee: '',  // 电费
      car_seat: '',         // 停车位
      rem: '',              // 项目说明
      facility: '',         // 配套设施
      equipment: '',        // 楼宇设备
      traffic: '',          // 交通状况
      environment: '',      // 周边环境
      commission: '',       // 佣金比例
      user_id: 0,
      user: '',
      avatar: '',
      create_time_text: '',
      short_url: '',
      share: 0,
      status : 0,
      key: '',
      isFavorite: false,
      allowEdit: false,
      allowCopy: false,
      allowCopy2: false,
      allowAudit: false,
      allowDelete: false,
      images: [],
      attach: [],
      linkman: [],
      unit: [],
      confirm: []
    },
    previewImages: [],
    showAudit: false,
    auditSummary: '',
    auditError: false,
    showCustomerPicker: false,
    customerData: [],
    customer_id: 0,
    tapStartTime: 0,
    tapEndTime: 0,
    showAttachActions: false,
    attachIndex: 0,
    attachActions: [
      {
        name: '删除',
      }
    ],
    uploadAccept: 'media'
  },
  
  onLoad(options) {
    let that = this
    app.globalData.refreshBuildingView = false
    if (app.globalData.isWindows) {
      that.setData({
        uploadAccept: 'image'
      })
    }
    if (options.id) {
      that.data.info.id = options.id
    }
    if (options.key) {
      that.data.info.key = options.key
    }
    if (app.globalData.appUserInfo) {
      this.setData({
        me: app.globalData.appUserInfo
      })
      that.getView()
      that.getCustomer()
    } else {
      app.userLoginCallback = () => {
        this.setData({
          me: app.globalData.appUserInfo
        })
        that.getView()
        that.getCustomer()
      }
    }
  },

  onShow: function () {
    if (app.globalData.refreshBuildingView) {
      this.getView()
      app.globalData.refreshBuildingView = false
    }
  },
  
  onPullDownRefresh: function() {
    // 触发下拉刷新时执行
    if (this.data.isLoading == false) {
      this.data.isPullDown = true
      this.getView()
    }
    wx.stopPullDownRefresh()
  },
  
  // 转发事件
  onShareAppMessage(object) {
    let data = this.data.info
    let shareData = {
      title: data.building_name,
      path: '/pages/building/view/view?id=' + data.id + '&key=' + data.key
    }
    if (this.data.info.images.length) {
      shareData.imageUrl = app.globalData.serverUrl + '/' + this.data.info.images[0].msrc
    }
    return shareData
  },

  onVideoPlay() {
    this.setData({
      isVideoPlay: true
    })
  },

  onVideoStop() {
    this.setData({
      isVideoPlay: false
    })
  },
  
  // 预览图片
  bindViewImage(event) {
    let idx = event.currentTarget.dataset.data
    wx.previewMedia({
      sources: this.data.previewImages,
      current: idx
    })
  },
  
  // 打开地图
  bindOpenLocation() {
    let info = this.data.info
    if (info.latitude !== null && info.latitude !== 0 && info.longitude !== null && info.longitude !== 0) {
      let latLng = app.convertBD09ToGCJ02(info.latitude, info.longitude)
      wx.openLocation({
        latitude: latLng.lat,
        longitude: latLng.lng,
        scale: 18,
        name: info.building_name,
        address: info.area + info.address
      })
    }
  },
  
  bindPhoneCall(event) {
    let data = event.currentTarget.dataset.data
    if (data.mobile || data.tel) {
      wx.makePhoneCall({
        phoneNumber: data.mobile || data.tel
      })
    }
  },
  
  bindCopyWeixin(event) {
    let data = event.currentTarget.dataset.data
    wx.setClipboardData({
      data: data.weixin || data.mobile,
      success (res) {
        wx.showToast({
          title: '微信号复制成功',
          duration: 1000
        })
      }
    })
  },

  bindCopyUrl(event) {
    wx.setClipboardData({
      data: this.data.info.short_url,
      success (res) {
        wx.showToast({
          title: '链接复制成功',
          duration: 1000
        })
      }
    })
  },

  bindViewNote: function() {
    wx.navigateTo({
      url: '../../web/web?url=' + app.globalData.serverUrl + '/index/building/' + this.data.info.id
    })
  },

  bindEdit: function(event) {
    wx.navigateTo({
      url: '../edit/edit?id=' + this.data.info.id
    })
  },

  bindCopy: function(event) {
    app.checkUser('/pages/building/edit/edit?id=' + this.data.info.id + '&copy=1')
  },

  bindAddUnit: function() {
    wx.navigateTo({
      url: '../../unit/edit/edit?bid=' + this.data.info.id
    })
  },

  bindAddLinkman: function() {
    wx.navigateTo({
      url: '../../linkman/edit/edit?type=building&oid=' + this.data.info.id
    })
  },

  bindAudit: function() {
    this.setData({
      auditSummary: '',
      auditError: false,
      showAudit: true
    })
  },

  auditSummaryChange: function(event) {
    this.data.auditSummary = event.detail
    if (event.detail.length > 0 && this.data.auditError) {
      this.setData({
        auditError: false
      })
    }
  },

  onAuditClose: function(event) {
    if (event.detail == 'cancel') {
      if (this.data.auditSummary.length == 0) {
        this.setData({
          showAudit: false
        })
        this.setData({
          auditError: true,
          showAudit: true
        })
      } else {
        this.setData({
          auditError: false
        })
        this.doAudit(2)
      }
    } else if (event.detail == 'confirm') {
      this.data.auditSummary = ''
      this.setData({
        auditError: false
      })
      this.doAudit(1)
    } else {
      this.setData({
        showAudit: false
      })
    }
  },

  doAudit: function(flag) {
    let that = this
    that.setData({
      showAudit: false
    })
    wx.showLoading()
    app.post('building/audit', {
      id: that.data.info.id,
      status: flag,
      summary: that.data.auditSummary
    }, (res) => {
      if (res.success) {
        app.globalData.refreshBuilding = true
        app.goBack()
      } else {
        Dialog.alert({
          title: '发生错误',
          message: res.message ? res.message : '系统异常'
        })
      }
    }, () => {
      wx.hideLoading()
    })
  },

  onCustomerPickerClose: function() {
    this.setData({
      showCustomerPicker: false
    })
  },

  onCustomerSelected: function(event) {
    this.setData({
      showCustomerPicker: false,
      customer_id: app.globalData.myCustomer[event.detail.index].id
    })
    if (this.data.customer_id > 0) {
      this.addFilter()
    }
  },

  getCustomer() {
    let that = this
    let list = []
    app.globalData.myCustomer.forEach((item) => {
      list.push(item.customer_name)
    })
    that.setData({
      customerData: list
    })
  },
  
  // 获取数据
  getView: function() {
    let that = this
    that.data.isLoading = true
    if (that.data.isPullDown == false) {
      wx.showLoading({
        title: '加载中',
      })
    } else {
      that.data.isPullDown = false
    }
    
    let url = 'building/detail?id=' + that.data.info.id
    if (that.data.info.key) {
      url += '&key=' + that.data.info.key
    }
    
    app.get(url, (res) => {
      if (res.success && res.data) {
        let prevList = []
        if (res.data.videos.length) {
          res.data.videos.forEach(element => {
            prevList.push({
              url: app.globalData.serverUrl + '/' + element.src,
              type: 'video',
              poster: app.globalData.serverUrl + '/' + element.msrc
            })
          })
        }
        if (res.data.images.length) {
          res.data.images.forEach(element => {
            prevList.push({
              url: app.globalData.serverUrl + '/' + element.src,
              type: 'image',
              poster: app.globalData.serverUrl + '/' + element.msrc
            })
          })
        }
        that.setData({
          info: res.data,
          previewImages: prevList
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
      that.data.isLoading = false
      wx.hideLoading()
    })
  },

  // 收藏
  favorite: function() {
    let that = this
    let url = that.data.info.isFavorite ? 'building/unFavorite' : 'building/favorite'
    wx.showLoading()
    app.post(url, {
      id: that.data.info.id
    }, (res) => {
      if (res.success) {
        wx.showToast({
          icon: 'none',
          title: that.data.info.isFavorite ? '已取消收藏' : '已加入收藏',
          duration: 1000
        })
        that.setData({
          ['info.isFavorite']: that.data.info.isFavorite ? false : true
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
  },

  bindDelete: function() {
    Dialog.confirm({
      title: '删除确认',
      message: '确定要删除这个项目吗？',
    })
    .then(() => {
      this.remove()
    })
    .catch(() => {
    });
  },

  remove: function() {
    let that = this
    wx.showLoading({
      title: '删除中',
    })
    app.post('building/remove', {
      id: that.data.info.id
    }, (res) => {
      if (res.success) {
        app.globalData.refreshBuilding = true
        app.goBack()
      } else {
        Dialog.alert({
          title: '发生错误',
          message: res.message ? res.message : '系统异常'
        })
      }
    }, () => {
      wx.hideLoading()
    })
  },

  toFilter: function() {
    let that = this
    if (that.data.customer_id == 0) {
      if (app.globalData.myCustomer.length > 1) {
        that.setData({
          showCustomerPicker: true
        })
        return
      } else if (app.globalData.myCustomer.length == 1) {
        that.data.customer_id = app.globalData.myCustomer[0].id
      } else {
        Dialog.alert({
          message: '您需要添加客户才可以添加拼盘'
        }).then(() => {
          wx.navigateTo({
            url: '../../customer/edit/edit',
          })
        })
        return
      }
    }
    that.addFilter()
  },

  addFilter: function() {
    let that = this
    wx.showLoading()
    app.post('customer/addFilter', {
      cid: that.data.customer_id,
      bids: that.data.info.id
    }, (res) => {
      if (res.success) {
        Dialog.alert({
          message: '所选项目已加入拼盘'
        }).then(() => {
          // wx.navigateTo({
          //   url: '../../customer/view/view?id=' + that.data.customer_id,
          // })
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
  },

  bindTouchStart: function(e) {
    this.tapStartTime = e.timeStamp;
  },

  bindTouchEnd: function(e) {
    this.tapEndTime = e.timeStamp;
  },

  bindAttachLongTap: function(event) {
    let idx = event.currentTarget.dataset.data
    if (this.data.info.allowEdit && app.globalData.appUserInfo.id == this.data.info.attach[idx].user_id) {
      this.setData({
        showAttachActions: true,
        attachIndex: idx
      })
    }
  },

  onAttachActionsClose: function() {
    this.setData({
      showAttachActions: false
    })
  },

  onAttachActionsSelect: function(event) {
    let that = this
    if (event.detail.name == '删除') {
      wx.showLoading()
      app.post('building/removeImage', {
        image_id: that.data.info.attach[that.data.attachIndex].id,
      }, (res) => {
        if (res.success) {
          that.data.info.attach.splice(that.data.attachIndex, 1)
          that.setData({
            ['info.attach']: that.data.info.attach
          })
        } else {
          wx.showToast({
            icon: 'none',
            title: res.message ? res.message : '操作失败，系统异常',
            duration: 2000
          })
        }
      }, () => {
        wx.hideLoading()
      })
    }
  },

  bindViewAttach: function (event) {
    if (this.tapEndTime  - this.tapStartTime > 350) {
      return
    }
    let attach = this.data.info.attach[event.currentTarget.dataset.data]
    let url = app.globalData.serverUrl + '/' + attach.url
    wx.showLoading({title: '加载中'})
    wx.downloadFile({
      url: url,
      success: function (res) {
        if (res.statusCode === 200) {
          wx.openDocument({
            showMenu: true,
            filePath: res.tempFilePath,
            success: function (res) {
            }
          })
        }
      },
      complete: function() {
        wx.hideLoading()
      }
    })
  },

  uploadAttach: function(event) {
    let that = this
    let count = 0
    let error = 0

    //app.checkSystem('/app/building/view/' + that.data.info.id)
    wx.chooseMessageFile({
      count: 5,
      success(res) {
        wx.showLoading({title: '上传中'})
        try {
          res.tempFiles.forEach(element => {
            wx.uploadFile({
              header: {
                'Content-Type': 'multipart/form-data',
                'User-Token': app.globalData.appUserInfo && app.globalData.appUserInfo.token ? 
                  app.globalData.appUserInfo.token : ''
              },
              url: app.globalData.serverUrl + '/api/building/uploadAttach',
              filePath: element.path,
              name: 'attach[]',
              formData: {
                'id': that.data.info.id,
                'name': element.name
              },
              success (res2) {
                if (res2.data) {
                  let json = JSON.parse(res2.data)
                  if (json.success) {
                    that.setData({
                      ['info.attach']: json.data
                    })
                  } else {
                    error++
                  }
                } else {
                  error++
                }
              },
              complete() {
                count++
                if (count >= res.tempFiles.length) {
                  wx.hideLoading()
                  if (error > 0) {
                    Dialog.alert({
                      title: '发生错误',
                      message: error + '个文件上传失败'
                    })
                  }
                }
              }, 
              fail(e) {
                error++
                console.log(e.errMsg)
              }
            })
          })
        } catch(e) {
          Dialog.alert({
            title: '发生错误',
            message: e.message
          }).then(() => {
          })
        }
      }
    })
  },

  uploadImage: function(event) {
    let that = this
    let count = 0
    let error = 0
    const files = event.detail.file
    if (files.length == 0) {
      return
    }
    wx.showLoading()
    let header = {
      'User-Token': app.globalData.appUserInfo && app.globalData.appUserInfo.token ? 
        app.globalData.appUserInfo.token : ''
    }
    if (!app.globalData.isWindows) {
      header['Content-Type'] = 'multipart/form-data'
    }
    try {
      files.forEach(file => {
        wx.uploadFile({
          header: header,
          url: app.globalData.serverUrl + '/api/building/uploadImage',
          filePath: that.data.uploadAccept == 'media' ? file.tempFilePath : file.path,
          name: 'images[]',
          formData: {
            'id': that.data.info.id,
            'is_default': count == 0 ? 1 : 0
          },
          success(res) {
            if (res.data) {
              let json = JSON.parse(res.data)
              if (json.success) {
                if (json.data && json.data.length) {
                  let imageList = []
                  json.data.forEach(element => {
                    imageList.push({
                      url: app.globalData.serverUrl + '/' + element.src,
                      type: element.is_image ? 'image' : 'video',
                      poster: app.globalData.serverUrl + '/' + element.msrc
                    })
                  })
                  that.setData({
                    previewImages: imageList
                  })
                }
              } else {
                error++
              }
            } else {
              error++
            }
          },
          complete() {
            if (count >= files.length) {
              //app.globalData.refreshBuildingView = true
              wx.hideLoading()
              if (error > 0) {
                Dialog.alert({
                  title: '发生错误',
                  message: error + '个文件上传失败'
                })
              }
            }
          },
          fail(e) {
            error++
            console.log(e.errMsg)
          }
        })
        count++
      })
    } catch(e) {
      wx.hideLoading()
      Dialog.alert({
        title: '发生错误',
        message: e.message
      })
    }
  }
})