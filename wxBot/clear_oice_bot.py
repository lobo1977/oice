#!/usr/bin/env python
# coding: utf-8
#

import traceback
import mysql_oice

def main():
    try:
        sql = "delete from tbl_robot"
        mysql_oice.execute_sql(sql)
        sql = "delete from tbl_robot_contact"
        mysql_oice.execute_sql(sql)
        sql = "delete from tbl_robot_task"
        mysql_oice.execute_sql(sql)

        print ("[INFO] Clear boot finished.")
    except Exception as e:
        print e.message, traceback.format_exc()
        
if __name__ == '__main__':
    main()