// pages/building/edit/edit.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';
import Toast from '../../../miniprogram_npm/@vant/weapp/toast/toast';

const app = getApp()

Page({
  data: {
    activeTab: 1,
    id: 0,
    copy: 0,
    areaData: [],
    showArea: false,
    type:[],
    showType: false,
    level: [{name: '甲级', value: '甲'},{name: '乙级', value: '乙'},{name: '丙级', value: '丙'},{name: '未定级', value: ''}],
    showLevel: false,
    rent_sell: [{name: '出租'}, {name: '出售'}, {name: '出租，出售'}],
    showRentSell: false,
    showCompletionDate: false,
    numberCompletionDate: Date.now(),
    info: {
      __token__: '',
      copy: 0,
      building_name: '',    // 名称
      type: '',             // 类别
      level: '',            // 等级
      area: '',             // 城区
      district: '',         // 商圈
      address: '',          // 地址
      longitude: 0,          // 经度
      latitude: 0,           // 纬度
      completion_date: '', // 竣工日期
      rent_sell: '',        // 租售
      price: '',            // 价格
      acreage: null,        // 建筑面积
      // usage_area: null,  // 使用面积
      // floor: '',            // 楼层
      // floor_area: null,     // 标准层面积
      floor_height: null,   // 层高
      bearing: null,        // 楼板承重
      developer: '',        // 开发商
      manager: '',          // 物业管理
      fee: '',              // 物业费
      // electricity_fee: '',  // 电费
      // car_seat: '',         // 停车位
      rem: '',              // 项目说明
      facility: '',         // 配套设施
      equipment: '',        // 楼宇设备
      traffic: '',          // 交通状况
      environment: '',      // 周边环境
      commission: '',       // 佣金比例
      company_id: '',       // 所属企业
      user_id: 0,
      share: 1,             // 是否公开
      send_sms: 0           // 是否发送委托确认短信
    },
    engInfo: {
      __token__: '',
      name: '',
      location: '',
      situation: '',
      developer: '',
      manager: '',
      network: '',
      elevator: '',
      hvac: '',
      amenities: '',
      tenants: ''
    },
    is_name_empty: false,
    name_error: '',
    is_eng_name_empty: false,
    eng_name_error: '',
    images: [],
    previewList: [],
    //attach: null,
    imageMenu: [
      {name: '设为封面', value: 'setDefault', disabled: true}, 
      {name: '删除', value: 'delete', disabled: true}
    ],
    showImageMenu: false,
    current_image_index: 0,
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
        title: options.copy == 1 ? '复制项目' : '修改项目信息'
      })
      that.setData({
        id: options.id,
        copy: options.copy ? options.copy : 0
      })
    } else {
      wx.setNavigationBarTitle({
        title: '添加项目'
      })
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
    let url = 'building/edit?id=' + that.data.id + '&copy=' + that.data.copy
    app.get(url, (res) => {
      if (res.success && res.data) {
        let arrType = []
        app.globalData.buildingType.forEach(element => {
          if (element.text != '类别') {
            arrType.push(element.text)
          }
        })

        let info = that.data.info
        for (let item in info) {
          if (res.data[item] !== undefined && res.data[item] !== null) {
            info[item] = res.data[item]
          }
        }
        if (res.data.user_id === 0 || res.data.user_id === app.globalData.appUserInfo.id) {
          info.send_sms = 1
        }
        if (res.data.completion_date) {
          info.completion_date = app.formatTime(Date.parse(res.data.completion_date.replace(/-/g, '/')), 'yyyy-MM-dd')
          that.setData({
            numberCompletionDate: Date.parse(res.data.completion_date.replace(/-/g, '/'))
          })
        }

        let engInfo = that.data.engInfo
        for (let item in engInfo) {
          if (res.data.engInfo && res.data.engInfo[item] !== undefined && res.data.engInfo[item] !== null) {
            engInfo[item] = res.data.engInfo[item]
          } else {
            engInfo[item] = ''
          }
        }
        engInfo.__token__ = res.data.__token__

        let imageList = []
        if (res.data.images) {
          res.data.images.forEach(element => {
            imageList.push({
              id: element.id,
              url: app.globalData.serverUrl + '/' + element.msrc,
              name: element.title,
              isImage: true,
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

        let arrArea = [], arrDistrict = [], area
        let idx = 0, idx2 = 0, areaIdx = 0, districtIdx = 0
        app.globalData.area.forEach(element => {
          if (element.id != 'all') {
            arrArea.push(element.text)
            if (res.data.area == element.text) {
              area = element
              areaIdx = idx
            }
            idx++
          }
        })

        if (area && area.children && area.children.length) {
          area.children.forEach(d => {
            if (d.id != '') {
              arrDistrict.push(d.text)
              if (res.data.district == d.text) {
                districtIdx = idx2
              }
              idx2++
            }
          })
        }

        that.setData({
          id: that.data.copy == 1 ? 0 : that.data.id,
          type: arrType,
          info: info,
          //attach: res.data.attach ? res.data.attach : null,
          engInfo: engInfo,
          images: imageList,
          areaData: [{values: arrArea, defaultIndex: areaIdx}, {values: arrDistrict, defaultIndex: districtIdx}]
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

  onNameInput: function(event) {
    this.data.info.building_name = event.detail
    if (this.data.info.building_name && this.data.is_name_empty) {
      that.setData({
        is_name_empty: false,
        name_error: ''
      })
    }
  },

  // onTelInput: function(event) {
  //   this.data.info.tel = event.detail
  // },

  // onLinkmanInput: function(event) {
  //   this.data.info.linkman = event.detail
  // },

  onAddressInput: function(event) {
    this.data.info.address = event.detail
  },

  bindSelectArea: function() {
    this.setData({
      showArea: true
    })
  },

  onAreaPickerChange(event) {
    const { picker, value, index } = event.detail
    let arrDistrict = []
    app.globalData.area.forEach(element => {
      if (element.text == value[0] && element.children && element.children.length) {
        element.children.forEach(d => {
          arrDistrict.push(d.text)
        })
      }
    })
    picker.setColumnValues(1, arrDistrict)
  },

  onAreaPickerClose: function() {
    this.setData({
      showArea: false
    })
  },

  onAreaSelected: function(event) {
    this.setData({
      ['info.area']: event.detail.value[0],
      ['info.district']: event.detail.value[1],
      showArea: false
    })
  },

  bindSelectType: function() {
    this.setData({
      showType: true
    })
  },

  onTypePickerClose: function() {
    this.setData({
      showType: false
    })
  },

  onTypeSelected: function(event) {
    this.setData({
      ['info.type']: event.detail.value,
      showType: false
    })
  },

  bindSelectLevel: function() {
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
      showLevel: false
    })
  },

  bindselectLatLng: function(event) {
    let that = this
    let info = that.data.info
    if (info.latitude !== null && info.latitude !== 0 && info.longitude !== null && info.longitude !== 0) {
      let latLng = app.convertBD09ToGCJ02(info.latitude, info.longitude)
      that.chooseLocation(latLng.lat, latLng.lng)
    } else {
      wx.authorize({
        scope: 'scope.userLocation',
        success (res) {
          wx.getLocation({
            type: 'gcj02',
            success (res) {
              that.chooseLocation(res.latitude, res.longitude)
            },
            fail (err) {
              console.log(err)
            }
          })
        }
      })
    }
  },

  chooseLocation: function(lat, lng) {
    let that = this
    wx.chooseLocation({
      latitude: lat,
      longitude: lng,
      success (res) {
        let latLng = app.convertGCJ02ToBD09(res.latitude, res.longitude)
        that.setData({
          ['info.address']: res.address,
          ['info.latitude']: latLng.lat,
          ['info.longitude']: latLng.lng,
        })
      }
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

  onAcreageInput: function(event) {
    this.data.info.acreage = event.detail
  },

  onPriceInput: function(event) {
    this.data.info.price = event.detail
  },

  onCommissionInput: function(event) {
    this.data.info.commission = event.detail
  },

  onFloorHeightInput: function(event) {
    this.data.info.floor_height = event.detail
  },

  onBearingInput: function(event) {
    this.data.info.bearing = event.detail
  },

  onDeveloperInput: function(event) {
    this.data.info.developer = event.detail
  },

  onManagerInput: function(event) {
    this.data.info.manager = event.detail
  },

  onFeeInput: function(event) {
    this.data.info.fee = event.detail
  },

  bindSelectCompletionDate: function() {
    this.setData({
      showCompletionDate: true
    })
  },

  onCompletionDateConfirm: function(event) {
    this.setData({
      ['info.completion_date']: app.formatTime(event.detail, 'yyyy-MM-dd'),
      showCompletionDate: false
    })
  },

  onCompletionDateClose: function() {
    this.setData({
      showCompletionDate: false
    })
  },

  onRemInput: function(event) {
    this.data.info.rem = event.detail
  },

  onEquipmentInput: function(event) {
    this.data.info.equipment = event.detail
  },

  onTrafficInput: function(event) {
    this.data.info.traffic = event.detail
  },

  onFacilityInput: function(event) {
    this.data.info.facility = event.detail
  },

  onEnvironmentInput: function(event) {
    this.data.info.environment = event.detail
  },

  onShareChange: function(event) {
    this.setData({
      ['info.share']: event.detail ? 1 : 0
    })
  },

  onSmsChange: function(event) {
    this.setData({
      ['info.send_sms']: event.detail ? 1 : 0
    })
  },

  onEngNameInput: function(event) {
    this.data.engInfo.name = event.detail
    if (this.data.engInfo.name && this.data.is_eng_name_empty) {
      that.setData({
        is_eng_name_empty: false,
        eng_name_error: ''
      })
    }
  },

  onEngLocationInput: function(event) {
    this.data.engInfo.location = event.detail
  },

  onEngSituationInput: function(event) {
    this.data.engInfo.situation = event.detail
  },

  onEngDeveloperInput: function(event) {
    this.data.engInfo.developer = event.detail
  },

  onEngManagerInput: function(event) {
    this.data.engInfo.manager = event.detail
  },

  onEngNetworkInput: function(event) {
    this.data.engInfo.network = event.detail
  },

  onEngElevatorInput: function(event) {
    this.data.engInfo.elevator = event.detail
  },

  onEngHvacInput: function(event) {
    this.data.engInfo.hvac = event.detail
  },

  onEngAmenitiesInput: function(event) {
    this.data.engInfo.amenities = event.detail
  },

  onEngTenantsInput: function(event) {
    this.data.engInfo.tenants = event.detail
  },

  bindSave: function() {
    let that = this
    if (!that.data.info.building_name) {
      that.setData({
        is_name_empty: true,
        name_error: '请输入项目名称'
      })
      return
    } else if (that.data.is_name_empty) {
      that.setData({
        is_name_empty: false,
        name_error: ''
      })
    }

    wx.showLoading({
      title: '保存中',
    })

    app.post('building/edit?id=' + that.data.id, that.data.info, (res) => {
      if (res.success) {
        app.globalData.refreshBuilding = true
        if (that.data.id == 0) {
          let id = res.data
          if (that.data.info.copy) {
            Toast({
              type: 'success',
              duration: 1000,
              message: '复制成功',
              onClose: () => {
                wx.redirectTo({
                  url: '../view/view?id=' + id
                })
              },
            })
          } else {
            wx.redirectTo({
              url: '../view/view?id=' + id
            })
          }
        } else {
          app.globalData.refreshBuildingView = true
          app.goBack()
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

  bindSaveEngInfo: function() {
    let that = this

    if (!that.data.engInfo.name) {
      that.setData({
        is_eng_name_empty: true,
        eng_name_error: '请输入项目名称'
      })
      return
    } else if (that.data.is_eng_name_empty) {
      that.setData({
        is_eng_name_empty: false,
        eng_name_error: ''
      })
    }

    wx.showLoading({
      title: '保存中',
    })

    app.post('building/saveEngInfo?id=' + that.data.id, that.data.engInfo, (res) => {
      if (res.data) {
        that.data.info.__token__ = res.data
        that.data.engInfo.__token__ = res.data
      }
      if (res.success) {
        wx.showToast({
          icon: 'none',
          title: '保存成功',
          duration: 2000
        })
      } else {
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
      if (event.detail.value == 'setDefault') {
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
      } else if (event.detail.value == 'delete') {
        that.removeImage()  
      }
    }
  },

  previewImages: function(event) {
    this.data.current_image_index = event.detail.index
    this.data.current_image = this.data.images[event.detail.index]
    let imageAction = this.data.imageMenu
    imageAction[0].disabled = !this.data.current_image.isImage || !this.data.current_image.deletable
    imageAction[1].disabled = !this.data.current_image.deletable
    this.setData({
      imageMenu: imageAction,
      showImageMenu: true
    })
    return
    // if (this.data.previewList.length) {
    //   wx.previewMedia({
    //     sources: this.data.previewList,
    //     current: event.detail.index
    //   })
    // }
  },

  uploadAttach: function(event) {
    let that = this
    if (app.globalData.isWindows) {
      return
    }

    wx.chooseMessageFile({
      count: 1,
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
                'id': that.data.id
              },
              success (res2) {
                if (res2.data) {
                  let json = JSON.parse(res2.data)
                  if (json.success) {
                    that.setData({
                      attach: json.data
                    })
                  } else {
                    Dialog.alert({
                      title: '发生错误',
                      message: json.message
                    }).then(() => {
                    })
                  }
                } else {
                  Dialog.alert({
                    title: '发生错误'
                  }).then(() => {
                  })
                }
              },
              complete() {
                wx.hideLoading()
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

  upload: function(event) {
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
      header['Content-Type'] =  'multipart/form-data'
    }
    try {
      files.forEach(file => {
        wx.uploadFile({
          header: header,
          url: app.globalData.serverUrl + '/api/building/uploadImage',
          filePath: that.data.uploadAccept == 'media' ? file.tempFilePath : file.path,
          name: 'images[]',
          formData: {
            'id': that.data.id,
            'is_default': count == 0 ? 1 : 0
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
                      isVideo: element.is_video,
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
              }
            } else {
              error++
            }
          },
          complete() {
            if (count >= files.length) {
              app.globalData.refreshBuildingView = true
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
  },

  removeImage: function(event) {
    let that = this
    let idx = that.data.current_image_index
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
          app.globalData.refreshBuildingView = true
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
    });
  }
})