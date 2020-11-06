// pages/building/edit/edit.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';

const app = getApp()

Page({
  data: {
    activeTab: 1,
    id: 0,
    face: [{name: '东', type: 'default'},{name: '西', type: 'default'},{name: '南', type: 'default'},{name: '北', type: 'default'}],
    showFace: false,
    rent_sell: [{name: '出租'}, {name: '出售'}, {name: '出租，出售'}],
    showRentSell: false,
    decoration: [{name: '毛坯', type: 'default'}, {name: '吊顶', type: 'default'}, {name: '白墙', type: 'default'}, {name: '墙纸', type: 'default'}, {name: '水泥地', type: 'default'}, {name: '地砖', type: 'default'}, {name: '地毯', type: 'default'}, {name: '地板', type: 'default'}, {name: '精装修', type: 'default'}, {name: '客户遗留装修', type: 'default'}],
    showDecoration: false,
    status: [ 
      {name: '空置', value: 1}, 
      {name: '已出（隐藏）', value: 2}
    ],
    showStatus: false,
    showEndDate: false,
    numberEndDate: Date.now(),
    maxEndDate: Date.now() + 365 * 24 * 60 * 60 * 1000,
    info: {
      __token__: '',
      building_id: 0,
      building_no: '',      // 楼栋
      floor: null,          // 楼层
      room: '',             // 房间号
      face: '',             // 朝向
      acreage: null,        // 面积
      rent_sell: '',        // 租售
      rent_price: null,     // 出租价格
      sell_price: null,     // 出售价格
      decoration: '',       // 装修状况
      status: 1,            // 状态(默认空置)
      end_date: '',         // 到期日
      rem: '',              // 备注
      user_id: 0,
      company_id: 0,        // 所属企业
      share: 1,             // 是否公开
      bool_share: true,
      linkman: '',          // 联系人
      mobile: ''            // 联系电话
    },
    is_room_empty: false,
    room_error: '',
    is_mobile_error: false,
    mobile_error: '',
    images: [],
    previewList: [],
    imageMenu: [{name: '设为封面'}],
    showImageMenu: false,
    current_image: null,
    uploadAccept: 'media'
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let that = this
    if (app.globalData.isWindows) {
      that.setData({
        uploadAccept: 'image'
      })
    }
    if (options.id) {
      wx.setNavigationBarTitle({
        title: '修改单元信息'
      })
      that.setData({
        id: options.id
      })
    } else {
      wx.setNavigationBarTitle({
        title: '添加单元'
      })
    }
    if (options.bid) {
      that.data.building_id = options.bid
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

  bindTabChange: function(event) {
    this.setData({
      activeTab: event.detail.index + 1
    })
  },

  // 获取数据
  getData: function() {
    let that = this
    wx.showLoading({
      title: '加载中',
    })
    let url = 'unit/edit?id=' + that.data.id
    app.get(url, (res) => {
      if (res.success && res.data) {
        let info = that.data.info
        for (let item in info) {
          if (res.data[item] !== undefined && res.data[item] !== null) {
            info[item] = res.data[item]
          }
        }
        if (res.data.end_date) {
          info.end_date = app.formatTime(Date.parse(res.data.end_date.replace(/-/g, '/')), 'yyyy-MM-dd')
          that.setData({
            numberEndDate: Date.parse(res.data.end_date.replace(/-/g, '/'))
          })
        }

        if (res.data.face) {
          that.face.forEach((element, idx) => {
            if (res.data.face.indexOf(element.name) >= 0) {
              element.type = 'success'
            }
          })
        }

        if (res.data.decoration) {
          that.decoration.forEach((element, idx) => {
            if (res.data.decoration.indexOf(element.name) >= 0) {
              element.type = 'success'
            }
          })
        }

        let imageList = []
        if (res.data.images) {
          res.data.images.forEach(element => {
            imageList.push({
              id: element.id,
              url: app.globalData.serverUrl + '/' + element.msrc,
              name: element.title,
              deletable: element.default != 1
            })
            that.data.previewList.push({
              url: app.globalData.serverUrl + '/' + element.src,
              type: 'image'
            })
          })
        }

        if (res.data.videos) {
          res.data.videos.forEach(element => {
            imageList.push({
              id: element.id,
              url: app.globalData.serverUrl + '/' + element.msrc,
              name: element.title,
              isImage: false,
              deletable: true
            })
            that.data.previewList.push({
              url: app.globalData.serverUrl + '/' + element.src,
              type: 'video',
              poster: app.globalData.serverUrl + '/' + element.msrc
            })
          })
        }
        
        that.setData({
          info: info,
          face: that.data.face,
          decoration: that.data.decoration,
          images: imageList
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

  onBuildingInput: function(event) {
    this.setData({
      ['info.building_no']: event.detail
    })
  },

  onFloorInput: function(event) {
    this.setData({
      ['info.floor']: event.detail
    })
  },

  onRoomInput: function(event) {
    this.setData({
      ['info.room']: event.detail
    })
  },

  bindSelectFace: function() {
    this.setData({
      showFace: true
    })
  },

  onFaceClose: function() {
    let strFace = ''
    this.data.face.forEach(d => {
      if (d.type == 'success') {
        strFace+= d.name
      }
    })
    this.setData({
      showFace: false,
      ['info.face']: strFace
    })
  },

  onFaceTap: function(event) {
    let idx = event.target.dataset.data
    let type = this.data.face[idx].type
    this.setData({
      ['face[' + idx + '].type']: type == 'success' ? 'default' : 'success'
    })
  },

  onAcreageInput: function(event) {
    this.setData({
      ['info.acreage']: event.detail
    })
  },

  bindSelectRentSell: function() {
    this.setData({
      showRentSell: true
    })
  },

  onRentSellClose: function() {
    this.setData({
      showRentSell: false
    })
  },

  onRentSellSelect: function(event) {
    this.setData({
      ['info.rent_sell']: event.detail.name,
      showRentSell: false
    })
  },

  onRentPriceInput: function(event) {
    this.setData({
      ['info.rent_price']: event.detail
    })
  },

  onSellPriceInput: function(event) {
    this.setData({
      ['info.sell_price']: event.detail
    })
  },

  bindSelectDecoration: function() {
    this.setData({
      showDecoration: true
    })
  },

  onDecorationClose: function() {
    let strDecoration = ''
    this.data.decoration.forEach(d => {
      if (d.type == 'success') {
        if (strDecoration.length) {
          strDecoration += ','
        }
        strDecoration+= d.name
      }
    })
    this.setData({
      showDecoration: false,
      ['info.decoration']: strDecoration
    })
  },

  onDecorationTap: function(event) {
    let idx = event.target.dataset.data
    let type = this.data.decoration[idx].type
    this.setData({
      ['decoration[' + idx + '].type']: type == 'success' ? 'default' : 'success'
    })
  },

  bindSelectStatus: function() {
    this.setData({
      showStatus: true
    })
  },

  onStatusClose: function() {
    this.setData({
      showStatus: false
    })
  },

  onStatusSelect: function(event) {
    this.setData({
      ['info.status']: event.detail.value,
      showStatus: false
    })
  },

  bindSelectEndDate: function() {
    this.setData({
      showEndDate: true
    })
  },

  onEndDateConfirm: function(event) {
    this.setData({
      ['info.end_date']: app.formatTime(event.detail, 'yyyy-MM-dd'),
      showEndDate: false
    })
  },

  onEndDateClose: function() {
    this.setData({
      showEndDate: false
    })
  },

  onRemInput: function(event) {
    this.data.info.rem = event.detail
  },

  onLinkmanInput: function(event) {
    this.data.info.linkman = event.detail
  },

  onMobileInput: function(event) {
    this.data.info.mobile = event.detail
    if (event.detail) {
      if (app.isMobile(event.detail)) {
        this.setData({
          mobile_error: ''
        })
      }
    }
  },

  onShareChange: function(event) {
    this.setData({
      ['info.share']: event.detail ? 1 : 0
    })
  },

  bindSave: function() {
    let that = this
    let error = 0
    if (!that.data.info.room) {
      error++
      that.setData({
        is_room_empty: true,
        room_error: '请输入房间号'
      })
    } else {
      that.setData({
        is_room_empty: false,
        room_error: ''
      })
    }

    if (that.data.info.mobile && !app.isMobile(that.data.info.mobile)) {
      error++
      that.setData({
        is_mobile_error: true,
        mobile_error: '请填写有效的手机号码'
      })
    } else {
      that.setData({
        is_mobile_error: false,
        mobile_error: ''
      })
    }

    if (error > 0) {
      return
    }

    wx.showLoading({
      title: '保存中',
    })

    app.post('unit/edit?id=' + that.data.id, that.data.info, (res) => {
      if (res.success) {
        app.globalData.refreshBuildingView = true
        if (that.data.id == 0) {
          let id = res.data
          wx.redirectTo({
            url: '../view/view?id=' + id
          })
        } else {
          app.globalData.refreshUnitView = true
          wx.navigateBack()
        }
      } else {
        if (res.data && res.data.token) {
          that.data.info.__token__ = res.data.token
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
  },

  onImageMenuClose: function() {
    this.setData({
      showImageMenu: false
    })
  },

  onImageMenuSelect: function(event) {
    let that = this
    that.setData({
      showImageMenu: false
    })
    if (that.data.current_image) {
      wx.showLoading()
      app.post('building/setDefaultImage', {
        image_id: that.data.current_image.id
      }, (res) => {
        if (res.success) {
          that.data.images.forEach(img => {
            if (img.id == that.data.current_image.id) {
              img.deletable = false
            } else {
              img.deletable = true
            }
          })
          that.setData({
            images: that.data.images
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

  previewImages: function(event) {
    this.data.current_image = this.data.images[event.detail.index]
    if (this.data.current_image.isImage && this.data.current_image.deletable) {
      this.setData({
        showImageMenu: true
      })
      return
    }
    if (this.data.previewList.length) {
      wx.previewMedia({
        sources: this.data.previewList,
        current: event.detail.index
      })
    }
  },

  upload: function(event) {
    let that = this
    let count = 0
    let error = 0
    const files = event.detail.file
    wx.showLoading()
    try {
      files.forEach(file => {
        wx.uploadFile({
          header: {
            'Content-Type': 'multipart/form-data',
            'User-Token': app.globalData.appUserInfo && app.globalData.appUserInfo.token ? 
              app.globalData.appUserInfo.token : ''
          },
          url: app.globalData.serverUrl + '/api/building/uploadUnitImage',
          filePath: file.tempFilePath,
          name: 'images[]',
          formData: {
            'id': that.data.id
          },
          success(res) {
            if (res.data) {
              let json = JSON.parse(res.data)
              if (json.success) {
                if (json.data && json.data.length) {
                  let imageList = []
                  that.data.previewList = []
                  json.data.forEach(element => {
                    imageList.push({
                      id: element.id,
                      url: app.globalData.serverUrl + '/' + element.msrc,
                      name: element.title,
                      isImage: element.is_image,
                      deletable: element.default != 1
                    })
                    that.data.previewList.push({
                      url: app.globalData.serverUrl + '/' + element.src,
                      type: element.is_image ? 'image' : 'video',
                      poster: app.globalData.serverUrl + '/' + element.msrc
                    })
                  })
                  that.setData({
                    images: imageList
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
            count++
            if (count >= files.length) {
              app.globalData.refreshUnitView = true
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
      wx.hideLoading()
      Dialog.alert({
        title: '发生错误',
        message: e.message
      })
    }
  },

  removeImage: function(event) {
    let that = this
    let idx = event.detail.index
    let file = that.data.images[idx]

    Dialog.confirm({
      title: '删除确认',
      message: '确定要删除这个图片/视频吗？',
    })
    .then(() => {
      wx.showLoading()
      app.post('building/removeImage', {
        image_id: file.id
      }, (res) => {
        if (res.success) {
          app.globalData.refreshUnitView = true
          that.data.images.splice(idx, 1)
          that.data.previewList.splice(idx, 1)
          that.setData({
            images: that.data.images
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
    })
    .catch(() => {
    })
  }
})