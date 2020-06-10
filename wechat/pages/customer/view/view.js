// pages/customer/view/view.js

const app = getApp()

Page({
  data: {
    isLoading: false,
    isPullDown: false,
    steps: [
      {
        text: '潜在'
      },
      {
        text: '跟进'
      },
      {
        text: '看房'
      },
      {
        text: '洽谈'
      },
      {
        text: '成交'
      },
    ],
    logs: [],
    previewImages: [],
    activeStep: 0,
    activeIcon: 'checked',
    activeColor: '#07c160',
    info: {
      id: 0,
      customer_name: '',    // 名称
      tel: '',              // 直线电话
      area: '',             // 城区
      address: '',          // 地址
      demand: '',           // 需求项目
      lease_buy: '',        // 租赁/购买
      district: '',         // 意向商圈
      acreage: '',          // 面积
      budget: '',           // 预算
      settle_date: '',      // 入驻日期
      current_area: '',     // 在驻面积
      end_date: '',         // 到日期
      rem: '',              // 备注
      status: 0,            // 状态
      clash: 0,             // 撞单
      user_id: 0,
      company_id: 0,
      manager: '',          // 客户经理
      avatar: '',
      manager_mobile: '',
      company: '',          // 所属企业
      key: '',
      allowEdit: false,
      allowTurn: false,
      allowFollow: false,
      allowConfirm: false,
      allowClash: false,
      allowDelete: false,
      linkman: [],            // 联系人
      log: [],                // 跟进纪要
      attach: [],             // 附件
      filter: [],             // 项目筛选表
      recommend: [],          // 推荐资料
      confirm: [],            // 确认书
      clashCustomer: null,
      shareList: []
    },
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let that = this;
    if (options.id) {
      that.data.info.id = options.id
    }
    if (options.key) {
      that.data.info.key = options.key
    }
    if (app.globalData.appUserInfo) {
      that.getView()
    } else {
      app.userLoginCallback = () => {
        that.getView()
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
    if (this.data.isLoading == false) {
      this.data.isPullDown = true
      this.getView()
    }
    wx.stopPullDownRefresh()
    this.data.isPullDown = false
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
    let data = this.data.info
    let shareData = {
      title: data.customer_name,
      path: '/pages/customer/view/view?id=' + data.id + '&key=' + data.key
    }
    return shareData
  },

  bindPhoneCall(event) {
    let data = event.currentTarget.dataset.data
    if (data) {
      wx.makePhoneCall({
        phoneNumber: data
      })
    }
  },

  bindCopyWeixin(event) {
    let data = event.currentTarget.dataset.data
    if (data) {
      wx.setClipboardData({
        data: data
      })
    }
  },

  bindViewUser: function (event) {
    let id = event.currentTarget.dataset.data
    wx.navigateTo({
      url: '../../contact/view/view?id=' + id
    })
  },

  bindViewCustomer: function (event) {
    let id = event.currentTarget.dataset.data
    wx.navigateTo({
      url: 'view?id=' + id
    })
  },

  bindViewAttach: function (event) {
    let attach = event.currentTarget.dataset.data
    let url = app.globalData.serverUrl + '/' + attach.src
    if (attach.is_image) {
      wx.previewImage({
        current: url,
        urls: this.data.previewImages
      })
    } else {
      wx.downloadFile({
        url: url,
        success: function (res) {
          const filePath = res.tempFilePath
          wx.openDocument({
            filePath: filePath,
            success: function (res) {
            }
          })
        }
      })
    }
  },

  bindViewBuilding: function (event) {
    let item = event.currentTarget.dataset.data
    if (item.unit_id) {
      wx.navigateTo({
        url: '../../building/unit/unit?id=' + item.unit_id
      })
    } else {
      wx.navigateTo({
        url: '../../building/view/view?id=' + item.building_id
      })
    }
  },

  bindAddLog: function(event) {
    let id = event.currentTarget.dataset.data
    wx.navigateTo({
      url: '../log/log?oid=' + id
    })
  },

  bindUpload: function(event) {
    let that = this
    wx.chooseMedia({
      maxDuration: 20,
      camera: 'back',
      success(res) {
        wx.showLoading({
          title: '上传中',
        })
        let count = 0
        res.tempFiles.forEach(element => {
          wx.uploadFile({
            header: {
              'Content-Type': 'multipart/form-data',
              'User-Token': app.globalData.appUserInfo && app.globalData.appUserInfo.token ? 
                app.globalData.appUserInfo.token : ''
            },
            url: app.globalData.serverUrl + '/api/customer/uploadAttach',
            filePath: element.tempFilePath,
            name: 'attach[]',
            formData: {
              'id': that.data.info.id
            },
            success (res2) {
              if (res2.data) {
                let json = JSON.parse(res2.data)
                if (json.success) {
                  var str = 'info.attach'
                  that.setData({
                    [str]: json.data
                  })
                  that.setPrevImages(json.data)
                }
              }
            },
            complete() {
              count++
              if (count >= res.tempFiles.length) {
                wx.hideLoading()
              }
            }
          })
        });
      }
    })
  },

  bindEdit: function(event) {
    let id = event.currentTarget.dataset.data
    wx.navigateTo({
      url: '../edit/edit?id=' + id
    })
  },

  setPrevImages: function(files) {
    if (files.length) {
      let prevList = this.data.previewImages
      for (let i = 0; i < files.length; i++) {
        if (files[i].is_image) {
          prevList.push(app.globalData.serverUrl + '/' + files[i].src)
        }
      }
      this.setData({
        previewImages: prevList
      })
    }
  },

  // 获取数据
  getView: function() {
    let that = this;
    that.setData({
      isLoading: true
    })
    
    if (that.data.isPullDown == false) {
      wx.showLoading({
        title: '加载中',
      })
    }
    
    let url = 'customer/detail?id=' + that.data.info.id
    if (that.data.info.key) {
      url += '&key=' + that.data.info.key
    }
    
    app.get(url, (res) => {
      if (res.data) {
        that.setData({
          info: res.data
        })

        if (that.data.info.status < 5) {
          that.setData({
            activeStep: that.data.info.status
          })
        } else if (that.data.info.status == 5) {
          that.setData({
            activeStep: 4,
            activeIcon: "clear",
            activeColor: "#ff0000"
          })
        }

        if (that.data.info.log && that.data.info.log.length) {
          let logs = []
          for (let i in that.data.info.log) {
            logs.push({
              text: that.data.info.log[i].title,
              desc: that.data.info.log[i].summary.replace(/<br\/>/g, '\n')
                + '\n' + that.data.info.log[i].user + ' ' + that.data.info.log[i].time_span
            })
          }
          that.setData({
            logs
          })
        }

        that.setPrevImages(res.data.attach)
      }
    }, () => {
      that.setData({
        isLoading: false
      })
      wx.hideLoading()
    })
  }
})