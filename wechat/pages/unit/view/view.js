//view.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';

//获取应用实例
const app = getApp()

Page({
  data: {
    isLoading: false,
    isPullDown: false,
    isVideoPlay: false,
    showQr: false,
    info: {
      id: 0,
      title: '',
      building_name: '',
      building_id: 0,
      building_no: '',
      floor: 0,          // 楼层
      room: '',          // 房间号
      face: '',          // 朝向
      acreage: 0,        // 面积
      rent_sell: '',     // 租售
      rent_price: 0,     // 出租价格
      sell_price: 0,     // 出售价格
      decoration: '',    // 装修状况
      status: 0,         // 状态
      statusText: '',
      end_date_text: '', // 到日期
      rem: '',           // 备注
      user_id: 0,
      user: '',
      avatar: '',
      create_time_text: '',
      key: '',
      isFavorite: false,
      allowNew: false,
      allowEdit: false,
      allowCopy: false,
      allowCopy2: false,
      allowDelete: false,
      images: [],
      linkman: []
    },
    previewImages: [],
    showCustomerPicker: false,
    customerData: [],
    customer_id: 0,
    showMenu: false,
    menu: [
      {name: '修改', value: 'edit', disabled: true}, 
      {name: '删除', value: 'delete', disabled: true}
    ],
    uploadAccept: 'media'
  },
  
  onLoad(options) {
    let that = this

    wx.showShareMenu({
      withShareTicket:true,
      menus:['shareAppMessage','shareTimeline']  
    })
    
    app.globalData.refreshUnitView = false
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
      that.getView()
      that.getCustomer()
    } else {
      app.userLoginCallback = () => {
        that.getView()
        that.getCustomer()
      }
    }
  },

  onShow: function () {
    if (app.globalData.refreshUnitView) {
      this.getView()
      app.globalData.refreshUnitView = false
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
      title: data.building_name + ' ' + data.title,
      path: '/pages/unit/view/view?id=' + data.id + '&key=' + data.key
    }
    if (this.data.info.images.length) {
      shareData.imageUrl = app.globalData.serverUrl + '/' + this.data.info.images[0].msrc
    }
    return shareData
  },

  onShareTimeline: function() {
    let data = this.data.info
    let shareData = {
      title: data.building_name + ' ' + data.title,
      query: 'id=' + data.id + '&key=' + data.key
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

  bindShowQr: function() {
    this.setData({
      showQr: true
    })
  },

  bindHideQr: function() {
    this.setData({
      showQr: false
    })
  },

  viewQrCode() {
    let urls = [ app.globalData.serverUrl + '/api/unit/qrcode/' + this.data.info.id + '.png' ]
    wx.previewImage({
      urls: urls,
      current: urls[0]
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
  
  bindPhoneCall(event) {
    let data = event.currentTarget.dataset.data
    if (data.mobile || data.tel) {
      wx.makePhoneCall({
        phoneNumber: data.mobile || data.tel
      })
    }
  },

  bindAddLinkman: function() {
    wx.navigateTo({
      url: '../../linkman/edit/edit?type=unit&oid=' + this.data.info.id
    })
  },
  
  bindCopyWeixin(event) {
    let data = event.currentTarget.dataset.data
    wx.setClipboardData({
      data: data.weixin || data.mobile
    })
  },

  bindViewNote: function() {
    wx.navigateTo({
      url: '../../web/web?url=' + app.globalData.serverUrl + '/index/unit/' + this.data.info.id
    })
  },

  downloadPdf: function() {
    let now = new Date()
    let fileName = `${now.getFullYear()}${now.getMonth() + 1 >= 10 ? (now.getMonth() + 1) : '0' + (now.getMonth() + 1)}${now.getDate() >= 10 ? now.getDate() : '0' + now.getDate()}${now.getHours() >= 10 ? now.getHours() : '0' + now.getHours()}${now.getMinutes() >= 10 ? now.getMinutes() : '0' + now.getMinutes()}${now.getSeconds() >= 10 ? now.getSeconds() : '0' + now.getSeconds()}.pdf`
    app.downloadPdfFile(fileName, '/index/unitPdf/' + this.data.info.id)
  },

  bindEdit: function(event) {
    wx.navigateTo({
      url: '../edit/edit?id=' + this.data.info.id
    })
  },

  bindCopy: function(event) {
    app.checkUser('/pages/unit/edit/edit?id=' + this.data.info.id + '&copy=1')
  },

  bindDelete: function() {
    Dialog.confirm({
      title: '删除确认',
      message: '确定要删除这个单元吗？',
    })
    .then(() => {
      this.remove()
    })
    .catch(() => {
    });
  },

  showMore: function() {
    this.setData({
      showMenu: true
    })
  },

  onMenuClose: function() {
    this.setData({
      showMenu: false
    })
  },

  onMenuSelect: function(event) {
    let that = this
    that.setData({
      showMenu: false
    })
    if (event.detail.value == 'edit') {
      that.bindEdit()
    } else if (event.detail.value == 'delete') {
      that.bindDelete()
    }
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
    
    let url = 'unit/detail?id=' + that.data.info.id
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

        let menuAction = that.data.menu
        menuAction[0].disabled = !res.data.allowEdit
        menuAction[1].disabled = !res.data.allowDelete

        that.setData({
          info: res.data,
          previewImages: prevList,
          menu: menuAction
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
    let url = that.data.info.isFavorite ? 'unit/unFavorite' : 'unit/favorite'
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

  remove: function() {
    let that = this
    wx.showLoading({
      title: '删除中',
    })
    app.post('unit/remove', {
      id: that.data.info.id
    }, (res) => {
      if (res.success) {
        app.globalData.refreshBuildingView = true
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
          message: '您需要添加可跟进客户才可以添加拼盘'
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
      uids: that.data.info.id
    }, (res) => {
      if (res.success) {
        Dialog.alert({
          message: '所选单元已加入拼盘'
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

  uploadImage: function(event) {
    let that = this
    let count = 0
    let error = 0
    const files = event.detail.file
    let header = {
      'User-Token': app.globalData.appUserInfo && app.globalData.appUserInfo.token ? 
        app.globalData.appUserInfo.token : ''
    }
    if (!app.globalData.isWindows) {
      header['Content-Type'] =  'multipart/form-data'
    }
    wx.showLoading()
    try {
      files.forEach(file => {
        wx.uploadFile({
          header: header,
          url: app.globalData.serverUrl + '/api/building/uploadUnitImage',
          filePath: file.url,
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
                console.log(json.message)
              }
            } else {
              error++
            }
          },
          complete() {
            if (count >= files.length) {
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