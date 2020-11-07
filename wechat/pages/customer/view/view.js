// pages/customer/view/view.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';

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
      isShare: false,
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
      shareList: [],
      allowEdit: false,
      allowTurn: false,
      allowFollow: false,
      allowConfirm: false,
      allowClash: false,
      allowDelete: false
    },
    tapStartTime: 0,
    tapEndTime: 0,
    showShareActions:false,
    shareIndex: 0,
    shareActions: [
      {
        name: '移除',
      }
    ],
    showAttachActions: false,
    attachIndex: 0,
    attachActions: [
      {
        name: '删除',
      }
    ],
    showTurn: false,
    searching: false,
    keyword: '',
    userList: [],
    showFilter: false,
    isFilterLoading: false,
    filterPage: 1,
    filterPageSize: 10,
    isFilterEnd: false,
    filterList: [],
    filterResult: []
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let that = this
    app.globalData.refreshCustomerView = false

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
    if (app.globalData.refreshCustomerView) {
      this.getView()
      app.globalData.refreshCustomerView = false
    }
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

  bindTouchStart: function(e) {
    this.tapStartTime = e.timeStamp;
  },

  bindTouchEnd: function(e) {
    this.tapEndTime = e.timeStamp;
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

  bindViewContact: function(event) {
    if (this.tapEndTime  - this.tapStartTime > 350) {
      return
    }
    let contact = this.data.info.shareList[event.currentTarget.dataset.data]
    wx.navigateTo({
      url: '../../contact/view/view?id=' + contact.id
    })
  },

  bindViewAttach: function (event) {
    if (this.tapEndTime  - this.tapStartTime > 350) {
      return
    }
    let attach = this.data.info.attach[event.currentTarget.dataset.data]
    let url = app.globalData.serverUrl + '/' + attach.url
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

  bindAddLinkman: function() {
    wx.navigateTo({
      url: '../../linkman/edit/edit?type=customer&oid=' + this.data.info.id
    })
  },

  bindAddLog: function(event) {
    wx.navigateTo({
      url: '../log/log?oid=' + this.data.info.id
    })
  },

  bindUpload: function(event) {
    let that = this
    let count = 0
    let error = 0

    app.checkSystem('/app/customer/view/' + that.data.info.id + '?tab=2')

    wx.chooseMessageFile({
      count: 5,
      success(res) {
        wx.showLoading()
        try {
          res.tempFiles.forEach(element => {
            wx.uploadFile({
              header: {
                'Content-Type': 'multipart/form-data',
                'User-Token': app.globalData.appUserInfo && app.globalData.appUserInfo.token ? 
                  app.globalData.appUserInfo.token : ''
              },
              url: app.globalData.serverUrl + '/api/customer/uploadAttach',
              filePath: element.path,
              name: 'attach[]',
              formData: {
                'id': that.data.info.id
              },
              success (res2) {
                if (res2.data) {
                  let json = JSON.parse(res2.data)
                  if (json.success) {
                    that.setData({
                      ['info.attach']: json.data
                    })
                    that.setPrevImages(json.data)
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

  bindUnShare: function(event) {
    let that = this
    Dialog.confirm({
      title: '取消确认',
      message: '确定不再查看这个客户了吗？',
    }).then(() => {
      that.unShare(app.globalData.appUserInfo.id)
    }).catch(() => {
    })
  },

  bindShareLongTap: function(event) {
    let idx = event.currentTarget.dataset.data
    if (this.data.info.allowEdit) {
      this.setData({
        showShareActions: true,
        shareIndex: idx
      })
    }
  },

  onShareActionsClose: function() {
    this.setData({
      showShareActions: false
    })
  },

  onShareActionsSelect: function(event) {
    let that = this
    if (event.detail.name == '移除') {
      let idx = that.data.shareIndex
      that.unShare(that.data.info.shareList[idx].id, idx)
    }
  },

  bindAttachLongTap: function(event) {
    let idx = event.currentTarget.dataset.data
    if (this.data.info.allowFollow && app.globalData.appUserInfo.id == this.data.info.attach[idx].user_id) {
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
      app.post('customer/removeAttach', {
        attach_id: that.data.info.attach[that.data.attachIndex].id,
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

  bindEdit: function(event) {
    wx.navigateTo({
      url: '../edit/edit?id=' + this.data.info.id
    })
  },

  bindDelete: function(event) {
    Dialog.confirm({
      title: '删除确认',
      message: '确定要删除这个客户吗？',
    })
    .then(() => {
      this.remove()
    })
    .catch(() => {
    });
  },

  bindTurn: function() {
    this.setData({
      userList: [],
      keyword: '',
      showTurn: true
    })
    this.searchUser('')
  },

  onTurnClose: function() {
    this.setData({
      showTurn: false
    })
  },

  onKewordChange: function(event) {
    this.data.keyword = event.detail
  },

  bindSearch: function() {
    this.searchUser()
  },

  bindSelectTurnUser: function(event) {
    let that = this
    let newUser = event.currentTarget.dataset.data
    //if (newUser.id === that.data.info.user_id) return
    Dialog.confirm({
      title: '转交确认',
      message: '确定要将客户转交给【' + newUser.title + '】吗？',
    }).then(() => {
      that.setData({
        showTurn: false
      })
      that.turn(newUser)
    }).catch(() => {
    })
  },

  bindAddFilter: function() {
    this.data.filterPage = 1
    this.setData({
      filterList: [],
      filterResult: [],
      showFilter: true
    })
    this.getFilter()
  },

  onFilterClose: function() {
    this.setData({
      showFilter: false
    })
  },

  bindFilterClick: function(event) {
    const idx = event.currentTarget.dataset.index
    const checkbox = this.selectComponent(`.filter-${idx}`)
    checkbox.toggle()
  },

  onFilterChange: function(event) {
    this.setData({
      filterResult: event.detail
    })
  },

  onFilterConfirm: function() {
    let that = this
    if (that.data.filterResult.length == 0) {
      wx.showToast({
        title: '请选择要加入筛选的项目',
        icon: 'none',
        duration: 2000
      })
      return
    }

    that.setData({
      showFilter: false
    })

    wx.showLoading()

    let bids = ''
    let uids = ''
    that.data.filterResult.forEach(element => {
      let arr = element.split(',')
      if (arr[0]) {
        if (bids) {
          bids += ','
        }
        bids += arr[0]
      }
      if (arr[1] != '' && arr[1] != '0') {
        if (uids) {
          uids += ','
        }
        uids += arr[1]
      }
    })
    app.post('customer/addFilter', {
      cid: that.data.info.id,
      bids: bids,
      uids: uids
    }, (res) => {
      if (res.success) {
        that.getView()
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

  setPrevImages: function(files) {
    if (files.length) {
      for (let i = 0; i < files.length; i++) {
        if (files[i].is_image) {
          this.data.previewImages.push(app.globalData.serverUrl + '/' + files[i].src)
        }
      }
    }
  },

  noop: function() {},

  bindRemoveFilter: function(event) {
    let that = this
    const idx = event.currentTarget.dataset.index
    const filter = that.data.info.filter[idx]
    wx.showLoading()
    app.post('customer/removeFilter', {
      id: that.data.info.id,
      building_id: filter.building_id,
      unit_id: filter.unit_id
    }, (res) => {
      if (res.success) {
        that.data.info.filter.splice(idx, 1)
        that.setData({
          ['info.filter']: that.data.info.filter
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

  // 获取数据
  getView: function() {
    let that = this
    that.data.isLoading = true

    if (that.data.isPullDown == false) {
      wx.showLoading()
    } else {
      that.data.isPullDown = false
    }
    
    let url = 'customer/detail?id=' + that.data.info.id
    if (that.data.info.key) {
      url += '&key=' + that.data.info.key
    }
    
    app.get(url, (res) => {
      if (res.success && res.data) {
        let arrSteps = that.data.steps
        let step = res.data.status
        let stepIcon = "checked"
        let stepColor = "#07c160"
        let logs = []

        arrSteps[4].text = '成交'
        if (step > 5) {
          step = 0
        } else if (step == 5) {
          step = 4
          arrSteps[4].text = '失败'
          stepIcon = "clear"
          stepColor = "#ff0000"
        }

        if (res.data.log && res.data.log.length) {
          for (let i in res.data.log) {
            logs.push({
              text: res.data.log[i].title,
              desc: res.data.log[i].summary.replace(/<br\/>/g, '\n')
                + '\n' + res.data.log[i].user + ' ' + res.data.log[i].time_span
            })
          }
        }

        that.setData({
          info: res.data,
          steps: arrSteps,
          activeStep: step,
          activeIcon: stepIcon,
          activeColor: stepColor,
          logs: logs
        })

        that.setPrevImages(res.data.attach)
      } else {
        Dialog.alert({
          title: '发生错误',
          message: res.message ? res.message : '系统异常'
        }).then(() => {
          wx.navigateBack()
        })
      }
    }, () => {
      that.data.isLoading = false
      wx.hideLoading()
    })
  },

  searchUser: function() {
    let that = this
    that.setData({
      searching:true
    })
    app.post('user/search', {
      company: app.globalData.appUserInfo.company_id,
      keyword: that.data.keyword,
    }, (res) => {
      if (res.success && res.data) {
        that.setData({
          userList: res.data
        })
      } else {
        that.setData({
          userList: []
        })
      }
    }, () => {
      that.setData({
        searching:false
      })
    })
  },

  turn: function(newUser) {
    let that = this
    wx.showLoading()
    app.post('customer/turn', {
      id: that.data.info.id,
      user_id: newUser.id,
      company_id: newUser.company_id
    }, (res) => {
      if (res.success) {
        Dialog.alert({
          message: '转交成功',
        }).then(() => {
          app.globalData.refreshCustomer = true
          wx.navigateBack()
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

  getMoreFilter: function() {
    if (!this.data.isFilterEnd) {
      this.data.filterPage++
      this.getFilter()
    }
  },

  getFilter: function() {
    let that = this

    if (that.data.isFilterLoading) 
      return

    that.setData({
      isFilterLoading: true
    })
    app.get('my/favorite?page=' + that.data.filterPage + '&page_size=' + that.data.filterPageSize, (res) => {
      if (res.success) {
        if (res.data && res.data.length) {
          let list = that.data.filterList.concat(res.data)
          that.setData({
            filterList: list,
            isFilterEnd: res.data.length < that.data.filterPageSize
          })
        } else {
          that.setData({
            isFilterEnd: true
          })
        }
      }
    }, () => {
      that.setData({
        isFilterLoading: false
      })
    })
  },

  unShare: function(user_id, idx) {
    let that = this
    wx.showLoading()
    app.post('customer/removeShare', {
      id: that.data.info.id,
      user_id: user_id,
    }, (res) => {
      if (res.success) {
        if (idx || idx === 0) {
          that.data.info.shareList.splice(idx, 1)
          that.setData({
            ['info.shareList']: that.data.info.shareList
          })
        } else {
          app.globalData.refreshCustomer = true
          wx.navigateBack()
        }
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
  },

  remove: function() {
    let that = this
    wx.showLoading({
      title: '删除中',
    })
    app.post('customer/remove', {
      id: that.data.info.id
    }, (res) => {
      if (res.success) {
        app.globalData.refreshCustomer = true
        wx.navigateBack()
      } else {
        Dialog.alert({
          title: '发生错误',
          message: res.message ? res.message : '系统异常'
        })
      }
    }, () => {
      wx.hideLoading()
    })
  }
})