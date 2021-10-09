define({ "api": [
  {
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "optional": false,
            "field": "varname1",
            "description": "<p>No type.</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "varname2",
            "description": "<p>With type.</p>"
          }
        ]
      }
    },
    "type": "",
    "url": "",
    "version": "0.0.0",
    "filename": "./apidoc/apidoc/main.js",
    "group": "D__xampp_htdocs_1ka_admin_survey_api_apidoc_apidoc_main_js",
    "groupTitle": "D__xampp_htdocs_1ka_admin_survey_api_apidoc_apidoc_main_js",
    "name": ""
  },
  {
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "optional": false,
            "field": "varname1",
            "description": "<p>No type.</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "varname2",
            "description": "<p>With type.</p>"
          }
        ]
      }
    },
    "type": "",
    "url": "",
    "version": "0.0.0",
    "filename": "./apidoc/main.js",
    "group": "D__xampp_htdocs_1ka_admin_survey_api_apidoc_main_js",
    "groupTitle": "D__xampp_htdocs_1ka_admin_survey_api_apidoc_main_js",
    "name": ""
  },
  {
    "type": "get",
    "url": "https://www.1ka.si/api/getSurveyAnswerState/survey/:id",
    "title": "getSurveyAnswerState",
    "name": "getSurveyAnswerState",
    "group": "Dashboard",
    "description": "<p>Get response rate for survey</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "Main Fields": [
          {
            "group": "Main Fields",
            "type": "Object[]",
            "optional": false,
            "field": "status",
            "description": "<p>Basic status of answers (3ll-entered intro, 4ll-entered frist page, 5ll-started responding, 5-partially completed, 6-completed)</p>"
          },
          {
            "group": "Main Fields",
            "type": "Object[]",
            "optional": false,
            "field": "usability",
            "description": "<p>Unit usability (unit (bottom usable limit/top usable limit))</p>"
          },
          {
            "group": "Main Fields",
            "type": "Object[]",
            "optional": false,
            "field": "breakoffs",
            "description": "<p>Data of responents breakoffs</p>"
          }
        ],
        "Data Fields": [
          {
            "group": "Data Fields",
            "type": "Number",
            "optional": false,
            "field": "freq",
            "description": "<p>Frequency</p>"
          },
          {
            "group": "Data Fields",
            "type": "String",
            "optional": false,
            "field": "state",
            "description": "<p>Realtive frequency</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\n\t\"status\": {\n\t\t\"3ll\": {\n\t\t\t\"freq\": 29,\n\t\t\t\"state\": \"100%\"\n\t\t},\n\t\t\"4ll\": {\n\t\t\t\"freq\": 27,\n\t\t\t\"state\": \"93%\"\n\t\t},\n\t\t\"5ll\": {\n\t\t\t\"freq\": 20,\n\t\t\t\"state\": \"69%\"\n\t\t},\n\t\t\"5\": {\n\t\t\t\"freq\": 18,\n\t\t\t\"state\": \"62%\"\n\t\t},\n\t\t\"6\": {\n\t\t\t\"freq\": 18,\n\t\t\t\"state\": \"62%\"\n\t\t}\n\t},\n\t\"usability\": {\n\t\t\"unit\": \"(50%\\/80%)\",\n\t\t\"usable\": {\n\t\t\t\"freq\": 1,\n\t\t\t\"state\": \"5%\"\n\t\t},\n\t\t\"partusable\": {\n\t\t\t\"freq\": 6,\n\t\t\t\"state\": \"30%\"\n\t\t},\n\t\t\"unusable\": {\n\t\t\t\"freq\": 13,\n\t\t\t\"state\": \"65%\"\n\t\t}\n\t},\n\t\"breakoffs\": {\n\t\t\"intro\": {\n\t\t\t\"freq\": 9,\n\t\t\t\"state\": \"31%\"\n\t\t},\n\t\t\"questionnaire\": {\n\t\t\t\"freq\": 0,\n\t\t\t\"state\": \"0% (neto 0%)\"\n\t\t},\n\t\t\"total\": {\n\t\t\t\"freq\": 9,\n\t\t\t\"state\": \"31%\"\n\t\t}\n\t}\n      }",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Dashboard"
  },
  {
    "type": "get",
    "url": "https://www.1ka.si/api/getSurveyDashboard/survey/:id",
    "title": "getSurveyDashboard",
    "name": "getSurveyDashboard",
    "group": "Dashboard",
    "description": "<p>Get all dashboard data of survey (if survey has no responses, only survey info is returned)</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "type": "Object[]",
            "optional": false,
            "field": "info",
            "description": "<p>Info of survey (basic dashboard info)</p>"
          },
          {
            "group": "Success 200",
            "type": "Object[]",
            "optional": true,
            "field": "statuses",
            "description": "<p>Statuses of responses of survey (optional)</p>"
          },
          {
            "group": "Success 200",
            "type": "Object[]",
            "optional": true,
            "field": "datetime",
            "description": "<p>Object of nubers of all responses by date and hour in day (optional)</p>"
          },
          {
            "group": "Success 200",
            "type": "Object[]",
            "optional": true,
            "field": "redirections",
            "description": "<p>Redirections of survey (optional)</p>"
          },
          {
            "group": "Success 200",
            "type": "Object[]",
            "optional": true,
            "field": "paradata",
            "description": "<p>Paradata of responses of survey (optional)</p>"
          },
          {
            "group": "Success 200",
            "type": "Object[]",
            "optional": true,
            "field": "responserate",
            "description": "<p>Response rate of survey (optional)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\n\t\"info\": [SEE OUTPUT OF FUNCTION getSurveyInfo],\n\t\"statuses\": [SEE OUTPUT OF FUNCTION getSurveyStatuses],\n\t\"datetime\": [SEE OUTPUT OF FUNCTION getSurveyDateTimeRange],\n\t\"redirections\": [SEE OUTPUT OF FUNCTION getSurveyDateTimeRange],\n\t\"paradata\": [SEE OUTPUT OF FUNCTION getSurveyParadata],\n\t\"responserate\": [SEE OUTPUT OF FUNCTION getSurveyAnswerState]\n      }",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Dashboard"
  },
  {
    "type": "get",
    "url": "https://www.1ka.si/api/getSurveyDateTimeRange/survey/:id",
    "title": "getSurveyDateTimeRange",
    "name": "getSurveyDateTimeRange",
    "group": "Dashboard",
    "description": "<p>Get object of nubers of all responses by date and hour in day (keys as date and hour in day, values as number of answers at that time)</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ]
      }
    },
    "success": {
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\n\t\"2017-10-02 09\": \"10\",\n\t\"2017-10-03 13\": \"1\",\n\t\"2017-11-10 11\": \"3\",\n\t\"2017-11-10 12\": \"7\",\n\t\"2017-11-10 13\": \"1\",\n\t\"2017-11-10 14\": \"7\",\n        \"2017-11-10 17\": \"2\"\n      }",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Dashboard"
  },
  {
    "type": "get",
    "url": "https://www.1ka.si/api/getSurveyParadata/survey/:id",
    "title": "getSurveyParadata",
    "name": "getSurveyParadata",
    "group": "Dashboard",
    "description": "<p>Get paradata of responses of survey</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "type": "Object[]",
            "optional": false,
            "field": "valid",
            "description": "<p>Paradata of valid answers/respondents</p>"
          },
          {
            "group": "Success 200",
            "type": "Object[]",
            "optional": false,
            "field": "all",
            "description": "<p>Paradata of all (valid and nonvalid) answers/respondents</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\n\t\"valid\": {\n\t\t\"unfilteredCount\": 3,\n\t\t\"allCount\": 2,\n\t\t\"pcCount\": \"2\",\n\t\t\"mobiCount\": 0,\n\t\t\"tabletCount\": 0,\n\t\t\"robotCount\": 0,\n\t\t\"jsActive\": 2,\n\t\t\"jsNonActive\": 0,\n\t\t\"jsUndefined\": 0,\n\t\t\"browser\": {\n\t\t\t\"Other\": \"2\"\n\t\t},\n\t\t\"os\": {\n\t\t\t\"Other\": \"2\"\n\t\t}\n\t},\n\t\"all\": {\n\t\t\"unfilteredCount\": 3,\n\t\t\"allCount\": 3,\n\t\t\"pcCount\": \"3\",\n\t\t\"mobiCount\": 0,\n\t\t\"tabletCount\": 0,\n\t\t\"robotCount\": 0,\n\t\t\"jsActive\": 3,\n\t\t\"jsNonActive\": 0,\n\t\t\"jsUndefined\": 0,\n\t\t\"browser\": {\n\t\t\t\"Other\": \"3\"\n\t\t},\n\t\t\"os\": {\n\t\t\t\"Other\": \"3\"\n\t\t}\n\t}\n      }",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Dashboard"
  },
  {
    "type": "get",
    "url": "https://www.1ka.si/api/getSurveyRedirections/survey/:id",
    "title": "getSurveyRedirections",
    "name": "getSurveyRedirections",
    "group": "Dashboard",
    "description": "<p>Get all redirections of survey</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ]
      }
    },
    "success": {
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\n\t\"3\": 0,\n\t\"4\": 0,\n\t\"5\": 0,\n\t\"6\": 0,\n\t\"valid\": {\n\t\t\"email\": 86,\n\t\t\"www.1ka.si\": 23,\n\t\t\"www.customsite.si\": 1\n\t},\n\t\"email\": 86,\n\t\"direct\": 4,\n\t\"cntAll\": 0\n      }",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Dashboard"
  },
  {
    "type": "get",
    "url": "https://www.1ka.si/api/getSurveyStatuses/survey/:id",
    "title": "getSurveyStatuses",
    "name": "getSurveyStatuses",
    "group": "Dashboard",
    "description": "<p>Get statuses of responses of survey</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "type": "Object",
            "optional": false,
            "field": "valid",
            "description": "<p>6-finished surveys, 5-partially finished surveys</p>"
          },
          {
            "group": "Success 200",
            "type": "Object",
            "optional": false,
            "field": "nonvalid",
            "description": "<p>6l-lurkers, 5l-lurkers, 4-click on survey, 3-click on intro, -1-unknown status</p>"
          },
          {
            "group": "Success 200",
            "type": "Object",
            "optional": false,
            "field": "invitation",
            "description": "<p>(non-surveyed units) 2-email sent (error), 1-email sent (non-response), 0-email not sent</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\"valid\":{\"6\":50,\"5\":0},\n \"nonvalid\":{\"6l\":0,\"5l\":0,\"4\":0,\"3\":0,\"-1\":0},\n \"invitation\":{\"2\":0,\"1\":0,\"0\":0}}",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Dashboard"
  },
  {
    "type": "post",
    "url": "https://www.1ka.si/api/addLink/survey/:id",
    "title": "addLink",
    "name": "addLink",
    "group": "Data_and_analysis",
    "description": "<p>Add new public link (hash link of data or analysis). Example of hash (public) link: https://www.1ka.si/podatki/50/5BABEC6D/ ([SITE_ROOT]/podatki/[SURVEY_ID]/[HASH_CODE]/)</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "GET parameter": [
          {
            "group": "GET parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ],
        "POST parameter": [
          {
            "group": "POST parameter",
            "type": "String",
            "optional": false,
            "field": "a",
            "description": "<p>Broad type of content of hash link (analysis, data) (if data, parameter m is not needed)</p>"
          },
          {
            "group": "POST parameter",
            "type": "String",
            "optional": false,
            "field": "m",
            "description": "<p>Specific type of content of hash link (analysis_creport, descriptor, frequency, charts, sumarnik) (when parameter a is &quot;data&quot;, this parameter is not needed)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Post-example: ",
          "content": "{\"a\":\"analysis\", \"m\":\"frequency\"}",
          "type": "json"
        }
      ]
    },
    "success": {
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\"note\":\"Link added\"}",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Data_and_analysis"
  },
  {
    "type": "delete",
    "url": "https://www.1ka.si/api/deleteLink/survey/:id",
    "title": "deleteLink",
    "name": "deleteLink",
    "group": "Data_and_analysis",
    "description": "<p>Delete specific public link (hash link of data or analysis)</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "GET parameter": [
          {
            "group": "GET parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ],
        "POST parameter": [
          {
            "group": "POST parameter",
            "type": "String",
            "optional": false,
            "field": "hash",
            "description": "<p>Hash code of public link to delete</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Post-example: ",
          "content": "{\"hash\":\"5BABEC6D\"}",
          "type": "json"
        }
      ]
    },
    "success": {
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\"note\":\"Link deleted\"}",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Data_and_analysis"
  },
  {
    "type": "delete",
    "url": "https://www.1ka.si/api/deleteSurveyUnit/survey/:id",
    "title": "deleteSurveyUnit",
    "name": "deleteSurveyUnit",
    "group": "Data_and_analysis",
    "description": "<p>Delete unit/response in survey data (whole response of a respondent)</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "GET parameter": [
          {
            "group": "GET parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ],
        "POST parameter": [
          {
            "group": "POST parameter",
            "type": "String",
            "optional": false,
            "field": "srv_unit_id",
            "description": "<p>ID of unit/response to delete</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Post-example: ",
          "content": "{\"srv_unit_id\":\"12774\"}",
          "type": "json"
        }
      ]
    },
    "success": {
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\"note\":\"Survey unit deleted\"}",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Data_and_analysis"
  },
  {
    "type": "get",
    "url": "https://www.1ka.si/api/getSurveyFrequencies/survey/:id",
    "title": "getSurveyFrequencies",
    "name": "getSurveyFrequencies",
    "group": "Data_and_analysis",
    "description": "<p>Get frequencies for all radio, checkbox, dropdown and plain text questions in the survey</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "Question Fields": [
          {
            "group": "Question Fields",
            "type": "String",
            "optional": false,
            "field": "besedilo_vprasanja",
            "description": "<p>Text of question</p>"
          },
          {
            "group": "Question Fields",
            "type": "String",
            "optional": false,
            "field": "id_vprasanja",
            "description": "<p>Id of question (left side of '_' is actual ID of question, right side is ID of sequence within question)</p>"
          },
          {
            "group": "Question Fields",
            "type": "String",
            "optional": false,
            "field": "vrsta_vprasanja",
            "description": "<p>Code of question type: 0-single choice (radio, dropdown), 1-multiple choice (checkbox), 2-text</p>"
          },
          {
            "group": "Question Fields",
            "type": "Object",
            "optional": false,
            "field": "odgovori",
            "description": "<p>Answers</p>"
          }
        ],
        "Answer Fields": [
          {
            "group": "Answer Fields",
            "type": "Object",
            "optional": false,
            "field": "invalid",
            "description": "<p>Invalid answers</p>"
          },
          {
            "group": "Answer Fields",
            "type": "Number",
            "optional": false,
            "field": "invalidCnt",
            "description": "<p>Count of all invalid answers</p>"
          },
          {
            "group": "Answer Fields",
            "type": "Number",
            "optional": false,
            "field": "allCnt",
            "description": "<p>Count of all answers</p>"
          },
          {
            "group": "Answer Fields",
            "type": "Number",
            "optional": false,
            "field": "validCnt",
            "description": "<p>Count of all valid answers</p>"
          },
          {
            "group": "Answer Fields",
            "type": "Object[]",
            "optional": false,
            "field": "valid",
            "description": "<p>Array of all valid asnwers</p>"
          },
          {
            "group": "Answer Fields",
            "type": "String",
            "optional": false,
            "field": "naslov",
            "description": "<p>Text/name/title of answer/choice (not in single choice)</p>"
          }
        ],
        "Valid answer Fields - single choice": [
          {
            "group": "Valid answer Fields - single choice",
            "type": "String",
            "optional": false,
            "field": "text",
            "description": "<p>Text/name/title of answer/choice</p>"
          },
          {
            "group": "Valid answer Fields - single choice",
            "type": "String",
            "optional": false,
            "field": "text_graf",
            "description": "<p>Text of answer/choice in graph</p>"
          },
          {
            "group": "Valid answer Fields - single choice",
            "type": "String",
            "optional": false,
            "field": "cnt",
            "description": "<p>Count of choices for this answer</p>"
          },
          {
            "group": "Valid answer Fields - single choice",
            "type": "Number",
            "optional": false,
            "field": "vrednost",
            "description": "<p>Value of answer/choice</p>"
          }
        ],
        "Valid answer Fields - multiple choice": [
          {
            "group": "Valid answer Fields - multiple choice",
            "type": "String",
            "optional": false,
            "field": "text",
            "description": "<p>0-not checked, 1-checked</p>"
          }
        ],
        "Valid answer Fields - text": [
          {
            "group": "Valid answer Fields - text",
            "type": "String",
            "optional": false,
            "field": "text",
            "description": "<p>Actual text asnwer</p>"
          },
          {
            "group": "Valid answer Fields - text",
            "type": "Number",
            "optional": false,
            "field": "cnt",
            "description": "<p>Count of same asnwer</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Success-Response:",
          "content": "[{\n                \"besedilo_vprasanja\": \"Best counrty in Europe\",\n                \"id_vprasanja\": \"118_0\",\n                \"vrsta_vprasanja\": \"0\",\n                \"odgovori\": {\n                        \"invalid\": {\n                                \"-1\": {\"text\": \"Unanswered question\",\"cnt\": \"1\"},\n                                \"-2\": {\"text\": \"Skipped question (IF logic)\",\"cnt\": 0},\n                                \"-3\": {\"text\": \"Drop-out\",\"cnt\": 0},\n                                \"-4\": {\"text\": \"Subsequent question\",\"cnt\": 0},\n                                \"-5\": {\"text\": \"Empty unit\",\"cnt\": 0},\n                                \"-97\": {\"text\": \"Invalid\",\"cnt\": 0},\n                                \"-98\": {\"text\": \"Refused\",\"cnt\": 0},\n                                \"-99\": {\"text\": \"Don&#39;t know\",\"cnt\": 0}\n                        },\n                        \"invalidCnt\": 1,\n                        \"allCnt\": 5,\n                        \"validCnt\": 4,\n                        \"valid\": [{\n                                \"text\": \"Slovenia\",\n                                \"text_graf\": \"Slovenia\",\n                                \"cnt\": \"1\",\n                                \"vrednost\": 1\n                        }, {\n                                \"text\": \"Spain\",\n                                \"text_graf\": \"Spain\",\n                                \"cnt\": 0,\n                                \"vrednost\": 2\n                        }, {\n                                \"text\": \"Germany\",\n                                \"text_graf\": \"Germany\",\n                                \"cnt\": \"2\",\n                                \"vrednost\": 3\n                        }, {\n                                \"text\": \"Other:\",\n                                \"text_graf\": \"Other:\",\n                                \"cnt\": \"1\",\n                                \"vrednost\": 4\n                        }, {\n                                \"text\": \"estonia\",\n                                \"cnt\": 1,\n                                \"text_graf\": null,\n                                \"other\": \"Other:\",\n                                \"vrednost\": \"estonia\"\n                        }]\n                }\n        }, {\n                \"besedilo_vprasanja\": \"Cities you visited\",\n                \"id_vprasanja\": \"119_0\",\n                \"vrsta_vprasanja\": \"1\",\n                \"odgovori\": [{\n                        \"invalid\": {[SEE FIRST QUESTION]},\n                        \"invalidCnt\": 1,\n                        \"allCnt\": 5,\n                        \"valid\": [{\n                                \"text\": \"0\",\n                                \"text_graf\": null,\n                                \"cnt\": \"1\"\n                        }, {\n                                \"text\": \"1\",\n                                \"text_graf\": null,\n                                \"cnt\": \"3\"\n                        }],\n                        \"validCnt\": 4,\n                        \"naslov\": \"Ljubljana\"\n                }, {\n                        \"invalid\": {[SEE FIRST QUESTION]},\n                        \"invalidCnt\": 1,\n                        \"allCnt\": 5,\n                        \"valid\": [{\n                                \"text\": \"0\",\n                                \"text_graf\": null,\n                                \"cnt\": \"3\"\n                        }, {\n                                \"text\": \"1\",\n                                \"text_graf\": null,\n                                \"cnt\": \"1\"\n                        }],\n                        \"validCnt\": 4,\n                        \"naslov\": \"Berlin\"\n                }, {\n                        \"invalid\": {[SEE FIRST QUESTION]},\n                        \"invalidCnt\": 1,\n                        \"allCnt\": 5,\n                        \"valid\": [{\n                                \"text\": \"0\",\n                                \"text_graf\": null,\n                                \"cnt\": \"2\"\n                        }, {\n                                \"text\": \"1\",\n                                \"text_graf\": null,\n                                \"cnt\": \"2\"\n                        }],\n                        \"validCnt\": 4,\n                        \"naslov\": \"Madrid\"\n                }, {\n                        \"invalid\": {[SEE FIRST QUESTION]},\n                        \"invalidCnt\": 1,\n                        \"allCnt\": 5,\n                        \"valid\": [{\n                                \"text\": \"0\",\n                                \"text_graf\": null,\n                                \"cnt\": \"3\"\n                        }, {\n                                \"text\": \"1\",\n                                \"text_graf\": null,\n                                \"cnt\": \"1\"\n                        }],\n                        \"validCnt\": 4,\n                        \"naslov\": \"London\"\n                }, {\n                        \"invalid\": {[SEE FIRST QUESTION]},\n                        \"invalidCnt\": 1,\n                        \"allCnt\": 5,\n                        \"valid\": [{\n                                \"text\": \"0\",\n                                \"text_graf\": null,\n                                \"cnt\": 0\n                        }, {\n                                \"text\": \"1\",\n                                \"text_graf\": null,\n                                \"cnt\": \"4\"\n                        }],\n                        \"validCnt\": 4,\n                        \"naslov\": \"Other:\"\n                }, {\n                        \"invalid\": {[SEE FIRST QUESTION]},\n                        \"invalidCnt\": 1,\n                        \"allCnt\": 5,\n                        \"validCnt\": 4,\n                        \"average\": null,\n                        \"valid\": [{\n                                \"text\": \"paris\",\n                                \"cnt\": 1,\n                                \"text_graf\": null,\n                                \"other\": \"Other:\"\n                        }, {\n                                \"text\": \"zagreb\",\n                                \"cnt\": 1,\n                                \"text_graf\": null,\n                                \"other\": \"Other:\"\n                        }, {\n                                \"text\": \"lisbon\",\n                                \"cnt\": 2,\n                                \"text_graf\": null,\n                                \"other\": \"Other:\"\n                        }],\n                        \"other\": \"Other:\"\n                }]\n        }, {\n                \"besedilo_vprasanja\": \"Write a name\",\n                \"id_vprasanja\": \"120_0\",\n                \"vrsta_vprasanja\": \"2\",\n                \"odgovori\": [{\n                        \"invalid\": {[SEE FIRST QUESTION]},\n                        \"invalidCnt\": 1,\n                        \"allCnt\": 5,\n                        \"validCnt\": 4,\n                        \"average\": null,\n                        \"valid\": [{\n                                \"text\": \"lucy\",\n                                \"cnt\": 1,\n                                \"text_graf\": null\n                        }, {\n                                \"text\": \"crish\",\n                                \"cnt\": 2,\n                                \"text_graf\": null\n                        }, {\n                                \"text\": \"marie\",\n                                \"cnt\": 1,\n                                \"text_graf\": null\n                        }]\n                }]\n       }]",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Data_and_analysis"
  },
  {
    "type": "get",
    "url": "https://www.1ka.si/api/getSurveyHashes/survey/:id",
    "title": "getSurveyHashes",
    "name": "getSurveyHashes",
    "group": "Data_and_analysis",
    "description": "<p>Get all hash links of survey. Example of hash (public) link: https://www.1ka.si/podatki/50/5BABEC6D/ ([SITE_ROOT]/podatki/[SURVEY_ID]/[HASH_CODE]/)</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "Main Fields": [
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "hash",
            "description": "<p>Hash code for link</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "comment",
            "description": "<p>Comment of hash link</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "refresh",
            "description": "<p>0-refresh mode off, 1-auto refresh site every x seconds</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "access_password",
            "description": "<p>If not NULL or &quot;&quot;, this password is needed to access public link</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "page",
            "description": "<p>Broad type of content of hash link (analysis, data)</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "add_date",
            "description": "<p>Date of creation</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "add_time",
            "description": "<p>Time of creation</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "email",
            "description": "<p>Email of author</p>"
          },
          {
            "group": "Main Fields",
            "type": "Object",
            "optional": false,
            "field": "properties",
            "description": "<p>Properties of hash link</p>"
          }
        ],
        "Hash link Fields": [
          {
            "group": "Hash link Fields",
            "type": "String",
            "optional": false,
            "field": "anketa",
            "description": "<p>ID of survey that hash link belong to</p>"
          },
          {
            "group": "Hash link Fields",
            "type": "String",
            "optional": false,
            "field": "a",
            "description": "<p>Broad type of content of hash link (analysis, data)</p>"
          },
          {
            "group": "Hash link Fields",
            "type": "String",
            "optional": false,
            "field": "m",
            "description": "<p>Specific type of content of hash link (analysis_creport, descriptor, frequency, charts, sumarnik)</p>"
          },
          {
            "group": "Hash link Fields",
            "type": "String",
            "optional": false,
            "field": "profile_id_status",
            "description": ""
          },
          {
            "group": "Hash link Fields",
            "type": "String",
            "optional": false,
            "field": "profile_id_variable",
            "description": ""
          },
          {
            "group": "Hash link Fields",
            "type": "String",
            "optional": false,
            "field": "profile_id_condition",
            "description": ""
          }
        ]
      },
      "examples": [
        {
          "title": "Success-Response:",
          "content": "[{\n               \"hash\": \"179A60BA\",\n               \"properties\": {\n                       \"anketa\": \"50\",\n                       \"a\": \"analysis\",\n                       \"m\": \"frequency\",\n                       \"profile_id_status\": 2,\n                       \"profile_id_variable\": 0,\n                       \"profile_id_condition\": 1\n               },\n               \"comment\": \"Frequencies\",\n               \"refresh\": \"0\",\n               \"access_password\": \"\",\n               \"page\": \"analysis\",\n               \"add_date\": \"17.05.2019\",\n               \"add_time\": \"12:38\",\n               \"email\": \"admin\"\n       }, {\n               \"hash\": \"F3FB9720\",\n               \"properties\": {\n                       \"anketa\": \"50\",\n                       \"a\": \"analysis\",\n                       \"m\": \"charts\",\n                       \"profile_id_status\": 2,\n                       \"profile_id_variable\": 0,\n                       \"profile_id_condition\": 1\n               },\n               \"comment\": \"Charts\",\n               \"refresh\": \"0\",\n               \"access_password\": \"\",\n               \"page\": \"analysis\",\n               \"add_date\": \"17.05.2019\",\n               \"add_time\": \"12:37\",\n               \"email\": \"admin\"\n       }, {\n               \"hash\": \"2D704440\",\n               \"properties\": {\n                       \"anketa\": \"50\",\n                       \"a\": \"data\",\n                       \"m\": \"\",\n                       \"profile_id_status\": 2,\n                       \"profile_id_variable\": 0,\n                       \"profile_id_condition\": 1\n               },\n               \"comment\": \"\",\n               \"refresh\": \"0\",\n               \"access_password\": null,\n               \"page\": \"data\",\n               \"add_date\": \"17.05.2019\",\n               \"add_time\": \"12:37\",\n               \"email\": \"admin\"\n       }, {\n               \"hash\": \"7A96B2C7\",\n               \"properties\": {\n                       \"anketa\": \"50\",\n                       \"a\": \"analysis\",\n                       \"m\": \"sumarnik\",\n                       \"profile_id_status\": 2,\n                       \"profile_id_variable\": 0,\n                       \"profile_id_condition\": 1\n               },\n               \"comment\": \"Summary\",\n               \"refresh\": \"0\",\n               \"access_password\": \"\",\n               \"page\": \"analysis\",\n               \"add_date\": \"17.05.2019\",\n               \"add_time\": \"12:36\",\n               \"email\": \"admin\"\n       }]",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Data_and_analysis"
  },
  {
    "type": "get",
    "url": "https://www.1ka.si/api/getSurveyResponseData/survey/:id?usr_id=333",
    "title": "getSurveyResponseData",
    "name": "getSurveyResponseData",
    "group": "Data_and_analysis",
    "description": "<p>Get basic info and all values/answers of response</p>",
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>ID of survey</p>"
          },
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "usr_id",
            "description": "<p>ID of response to analyse</p>"
          }
        ]
      }
    },
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "relevance",
            "description": "<p>Relevance of response (1-valid, 0-unvalid)</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "status",
            "description": "<p>Status code of response (6-Completed, 5-partially completed, 4-entered first page, 3-entered intro)</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "recnum",
            "description": "<p>Record number (sequence of response in survey)</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "itime",
            "description": "<p>Date of response</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": true,
            "field": "ALL_OTHERS",
            "description": "<p>Keys as names of values, values as answers</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\n        \"relevance (Relevance)\": \"1\",\n        \"status (Status)\": \"6\",\n        \"recnum (Record number)\": \"1\",\n        \"itime (Date)\": \"20.05.2019\",\n        \"Q1 (City)\": \"1\",\n        \"Q1_4_text (Other:)\": \"-2\",\n        \"Q2a (Slovenia)\": \"1\",\n        \"Q2b (Germany)\": \"1\",\n        \"Q2c (UK)\": \"0\",\n        \"Q2d (Other:)\": \"0\",\n        \"Q2d_text (Other:)\": \"-2\",\n        \"Q3 (Vpi\\u0161ite besedilo)\": \"Manja\"\n      }",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Data_and_analysis"
  },
  {
    "type": "post",
    "url": "https://www.1ka.si/api/copyQuestion/survey/:id",
    "title": "copyQuestion",
    "name": "copyQuestion",
    "group": "Questions_and_variables",
    "description": "<p>Make a copy of specific question and put it +1 in order to original question on same page</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "GET parameter": [
          {
            "group": "GET parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ],
        "POST parameter": [
          {
            "group": "POST parameter",
            "type": "Number",
            "optional": false,
            "field": "que_id",
            "description": "<p>ID of question to copy</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Post-example: ",
          "content": "{\"que_id\":12240}",
          "type": "json"
        }
      ]
    },
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "type": "Number",
            "optional": false,
            "field": "que_id",
            "description": "<p>ID of new question</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\"id\":12831,\"note\":\"Question copied\"}",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Questions_and_variables"
  },
  {
    "type": "post",
    "url": "https://www.1ka.si/api/createQuestion/survey/:id",
    "title": "createQuestion",
    "name": "createQuestion",
    "group": "Questions_and_variables",
    "description": "<p>Add new question to survey, put it on last spot of given group/page in survey</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "GET parameter": [
          {
            "group": "GET parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey to add new question to</p>"
          }
        ],
        "POST parameter": [
          {
            "group": "POST parameter",
            "type": "String",
            "optional": false,
            "field": "title",
            "description": "<p>Text of question</p>"
          },
          {
            "group": "POST parameter",
            "type": "Number",
            "optional": true,
            "field": "group_id",
            "description": "<p>Id of page/group to put question in (default is last page/group)</p>"
          },
          {
            "group": "POST parameter",
            "type": "Number",
            "optional": false,
            "field": "type_code",
            "description": "<p>Type of question (0-radio, 1-checkbox, 2-text)</p>"
          },
          {
            "group": "POST parameter",
            "type": "Number",
            "optional": true,
            "field": "reminder",
            "description": "<p>Reminder code for question (0-no reminder, 1-soft reminder, 2-hard reminder) (default is 0)</p>"
          },
          {
            "group": "POST parameter",
            "type": "String",
            "optional": true,
            "field": "other",
            "description": "<p>Text of option other to add (for cshoose type questions)</p>"
          },
          {
            "group": "POST parameter",
            "type": "Number",
            "optional": true,
            "field": "taSize",
            "description": "<p>Height size in lines of text field (for text question) (default is single line)</p>"
          },
          {
            "group": "POST parameter",
            "type": "String[]",
            "optional": true,
            "field": "options",
            "description": "<p>Array of options to add to question (for cshoose type questions)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Post-example (For choice-type): ",
          "content": "      {\n\t\"question\": {\n\t\t\t\"title\": \"This is text of choice type question\",\n\t\t\t\"type_code\": 1,\n                        \"group_id\": 2027,\n\t\t\t\"reminder\": 0,\n\t\t\t\"other\": \"Other:\",\n\t\t\t\"options\": [\"Text of option 1\", \"Text of option 2\", \"Text of option 3\"]\n\t\t}\t\n      }",
          "type": "json"
        },
        {
          "title": "Post-example (For text-type): ",
          "content": "      {\n\t\"question\": {\n\t\t\t\"title\": \"This is text of text type question\",\n\t\t\t\"type_code\": 2,\n                        \"group_id\": 2027,\n\t\t\t\"reminder\": 1,\n\t\t\t\"taSize\": 3\n\t\t}\t\n      }",
          "type": "json"
        }
      ]
    },
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "que_id",
            "description": "<p>ID of new question</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\"que_id\":5056,\"note\":\"Question created\"}",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Questions_and_variables"
  },
  {
    "type": "delete",
    "url": "https://www.1ka.si/api/deleteOption/survey/:id",
    "title": "deleteOption",
    "name": "deleteOption",
    "group": "Questions_and_variables",
    "description": "<p>Delete option/value of question (for picking type of question - single or multiple choice)</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "GET parameter": [
          {
            "group": "GET parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ],
        "POST parameter": [
          {
            "group": "POST parameter",
            "type": "String",
            "optional": false,
            "field": "option_id",
            "description": "<p>ID of option/value to delete</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Post-example: ",
          "content": "{\"option_id\":\"424\"}",
          "type": "json"
        }
      ]
    },
    "success": {
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\"note\":\"Option deleted\"}",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Questions_and_variables"
  },
  {
    "type": "delete",
    "url": "https://www.1ka.si/api/deleteQuestion/survey/:id",
    "title": "deleteQuestion",
    "name": "deleteQuestion",
    "group": "Questions_and_variables",
    "description": "<p>Delete question</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "GET parameter": [
          {
            "group": "GET parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ],
        "POST parameter": [
          {
            "group": "POST parameter",
            "type": "Number",
            "optional": false,
            "field": "que_id",
            "description": "<p>ID of question to delete</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Post-example: ",
          "content": "{\"que_id\":4240}",
          "type": "json"
        }
      ]
    },
    "success": {
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\"note\":\"Question deleted\"}",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Questions_and_variables"
  },
  {
    "type": "get",
    "url": "https://www.1ka.si/api/getSurveyQuestions/survey/:id",
    "title": "getSurveyQuestions",
    "name": "getSurveyQuestions",
    "group": "Questions_and_variables",
    "description": "<p>Get info of all questions of survey</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "Main Fields": [
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "id",
            "description": "<p>Id of question</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "tip",
            "description": "<p>Type of question (verbal)</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "naslov",
            "description": "<p>Title of question</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "info",
            "description": "<p>Additional information of question (e.g. &quot;Multiple answers are possible&quot;)</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "variable",
            "description": "<p>Short mark of question (question name)</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "stran_id",
            "description": "<p>Id of page</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "stran_naslov",
            "description": "<p>Title of page</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "vrstni_red",
            "description": "<p>Sequence number of the question</p>"
          },
          {
            "group": "Main Fields",
            "type": "Object",
            "optional": false,
            "field": "vrednosti",
            "description": "<p>Values o questions (possible answers)</p>"
          }
        ],
        "Value Fields": [
          {
            "group": "Value Fields",
            "type": "String",
            "optional": false,
            "field": "id",
            "description": "<p>Id of value in question</p>"
          },
          {
            "group": "Value Fields",
            "type": "String",
            "optional": false,
            "field": "naslov",
            "description": "<p>Title of value in question</p>"
          },
          {
            "group": "Value Fields",
            "type": "String",
            "optional": false,
            "field": "variable",
            "description": "<p>Short mark of value in question (value name)</p>"
          },
          {
            "group": "Value Fields",
            "type": "String",
            "optional": false,
            "field": "vrstni_red",
            "description": "<p>Sequence number of value in the question</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\"1234\":{\n     \"id\":\"1234\",\n     \"tip\":\"One answer\",\n     \"naslov\":\"Question tittle 1\",\n     \"info\":\"\",\n     \"variable\":\"Q1\",\n     \"stran_id\":\"2890\",\n     \"stran_naslov\":\"Page 1\",\n     \"vrstni_red\":\"1\",\n     \"vrednosti\":{\n         \"48495\":{\n             \"id\":\"48495\",\n             \"naslov\":\"Write text 1\",\n             \"variable\":\"1\",\n             \"vrstni_red\":\"1\"},\n          \"48496\":{\n             \"id\":\"48496\",\n             \"naslov\":\"Write text 2\",\n             \"variable\":\"2\",\n             \"vrstni_red\":\"2\"}\n     }\n }},\n {\"1235\"...",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Questions_and_variables"
  },
  {
    "type": "post",
    "url": "https://www.1ka.si/api/updateOrCreateOption/survey/:id",
    "title": "updateOrCreateOption",
    "name": "updateOrCreateOption",
    "group": "Questions_and_variables",
    "description": "<p>Update or add a value/option to question (for picking type of question - single or multiple choice)</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "GET parameter": [
          {
            "group": "GET parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ],
        "POST parameter": [
          {
            "group": "POST parameter",
            "type": "String",
            "optional": false,
            "field": "option_text",
            "description": "<p>Title/text of option/value</p>"
          },
          {
            "group": "POST parameter",
            "type": "String",
            "optional": true,
            "field": "que_id",
            "description": "<p>ID of question to add new option/value (needed only for adding)</p>"
          },
          {
            "group": "POST parameter",
            "type": "String",
            "optional": true,
            "field": "option_id",
            "description": "<p>ID of option/value to update (needed only for updating)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Post-example (adding): ",
          "content": "{\n  \"option_text\":\"First option\",\n  \"que_id\":\"3894\"\n}",
          "type": "json"
        },
        {
          "title": "Post-example (updating): ",
          "content": "{\n  \"option_text\":\"First option\",\n  \"option_id\":\"9618\"\n}",
          "type": "json"
        }
      ]
    },
    "success": {
      "examples": [
        {
          "title": "Success-Response (adding):",
          "content": "{\"note\":\"Option added\",\"opt_id\":9619}",
          "type": "json"
        },
        {
          "title": "Success-Response (updating):",
          "content": "{\"note\":\"Option updated\",\"opt_id\":\"9618\"}",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Questions_and_variables"
  },
  {
    "type": "post",
    "url": "https://www.1ka.si/api/updateQuestion/survey/:id",
    "title": "updateQuestion",
    "name": "updateQuestion",
    "group": "Questions_and_variables",
    "description": "<p>Update basic question properties</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "GET parameter": [
          {
            "group": "GET parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ],
        "POST parameter": [
          {
            "group": "POST parameter",
            "type": "String",
            "optional": false,
            "field": "id_que",
            "description": "<p>ID of question</p>"
          },
          {
            "group": "POST parameter",
            "type": "String",
            "optional": true,
            "field": "title",
            "description": "<p>Title/text of question</p>"
          },
          {
            "group": "POST parameter",
            "type": "String",
            "optional": true,
            "field": "reminder",
            "description": "<p>Reminder code for question (0-no reminder, 1-soft reminder, 2-hard reminder)</p>"
          },
          {
            "group": "POST parameter",
            "type": "String",
            "optional": true,
            "field": "other",
            "description": "<p>Text of option &quot;Other&quot; (update or add)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Post-example: ",
          "content": "      {\n\t\"question\": {\n            \"id_que\": \"8487\",\n            \"title\": \"Which city you like most?\",\n            \"reminder\": \"1\",\n            \"other\": \"Other:\"\n\t}\n      }",
          "type": "json"
        }
      ]
    },
    "success": {
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\"note\":\"Question updated\"}",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Questions_and_variables"
  },
  {
    "type": "post",
    "url": "https://www.1ka.si/api/BlockRepeatedIP/survey/:id",
    "title": "BlockRepeatedIP",
    "name": "BlockRepeatedIP",
    "group": "Surveys",
    "description": "<p>Block repeated IP (do not allow respondent to respond to survey again for the next x minutes)</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "GET parameter": [
          {
            "group": "GET parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ],
        "POST parameter": [
          {
            "group": "POST parameter",
            "type": "Number",
            "optional": true,
            "field": "blockIP",
            "description": "<p>In minutes - if this parameter is not set, blocking IP will be turned off (possible options are 10, 20, 30, 60, 720, 1440, 0-ip blocking off)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Post-example: ",
          "content": "{\"blockIP\":1440}",
          "type": "json"
        }
      ]
    },
    "success": {
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\"note\":\"IP blocking changed\"}",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Surveys"
  },
  {
    "type": "post",
    "url": "https://www.1ka.si/api/SurveyActivation/survey/:id",
    "title": "SurveyActivation",
    "name": "SurveyActivation",
    "group": "Surveys",
    "description": "<p>Activate (for 3 months from now) or deactivate survey (start it or stop it)</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "GET parameter": [
          {
            "group": "GET parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ],
        "POST parameter": [
          {
            "group": "POST parameter",
            "type": "Number",
            "optional": true,
            "field": "active",
            "description": "<p>If this parameter is not set, survey will be deactivated (0-deactivate survey, 1-activate survey)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Post-example: ",
          "content": "{\"active\":1}",
          "type": "json"
        }
      ]
    },
    "success": {
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\"note\":\"Survey activity changed\"}",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Surveys"
  },
  {
    "type": "put",
    "url": "https://www.1ka.si/api/copySurvey/survey/:id",
    "title": "copySurvey",
    "name": "copySurvey",
    "group": "Surveys",
    "description": "<p>Make a copy of specific survey</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "GET parameter": [
          {
            "group": "GET parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey to copy</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>ID of new survey</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\"id\":5194,\"note\":\"Survey copied\"}",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Surveys"
  },
  {
    "type": "post",
    "url": "https://www.1ka.si/api/createSurvey",
    "title": "createSurvey",
    "name": "createSurvey",
    "group": "Surveys",
    "description": "<p>Create survey with questions</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "Survey fields": [
          {
            "group": "Survey fields",
            "type": "String",
            "optional": false,
            "field": "naslov_vprasalnika",
            "description": "<p>Title/name of survey</p>"
          },
          {
            "group": "Survey fields",
            "type": "Number",
            "optional": false,
            "field": "survey_type",
            "description": "<p>Type of survey (0-voting, 2-survey)</p>"
          },
          {
            "group": "Survey fields",
            "type": "Object",
            "optional": false,
            "field": "uvod",
            "description": "<p>Introducrion data</p>"
          },
          {
            "group": "Survey fields",
            "type": "Number",
            "optional": true,
            "field": "hide_uvod",
            "description": "<p>Do we hide introduction (0-show, 1-hide, default is 0)</p>"
          },
          {
            "group": "Survey fields",
            "type": "Object",
            "optional": false,
            "field": "zakljucek",
            "description": "<p>Conclusion data</p>"
          },
          {
            "group": "Survey fields",
            "type": "Number",
            "optional": true,
            "field": "hide_zakljucek",
            "description": "<p>Do we hide conclusion (0-show, 1-hide, default is 0)</p>"
          },
          {
            "group": "Survey fields",
            "type": "String",
            "optional": false,
            "field": "besedilo",
            "description": "<p>Text of introduction or conclusion (set it on &quot;&quot; for default text)</p>"
          },
          {
            "group": "Survey fields",
            "type": "Object[]",
            "optional": true,
            "field": "vprasanja",
            "description": "<p>Array of all questions to add to survey</p>"
          }
        ],
        "Question fields": [
          {
            "group": "Question fields",
            "type": "String",
            "optional": false,
            "field": "besedilo_vprasanja",
            "description": "<p>Text of question</p>"
          },
          {
            "group": "Question fields",
            "type": "Number",
            "optional": false,
            "field": "mesto_vprasanja",
            "description": "<p>Order of question sequence to place this question in page</p>"
          },
          {
            "group": "Question fields",
            "type": "Number",
            "optional": false,
            "field": "vrsta_vprasanja",
            "description": "<p>Type of question (0-radio, 1-checkbox, 2-text)</p>"
          },
          {
            "group": "Question fields",
            "type": "Number",
            "optional": true,
            "field": "reminder",
            "description": "<p>Reminder code for question (0-no reminder, 1-soft reminder, 2-hard reminder) (default is 0)</p>"
          },
          {
            "group": "Question fields",
            "type": "String",
            "optional": true,
            "field": "other",
            "description": "<p>Text of option other to add (for radio and checkbox)</p>"
          },
          {
            "group": "Question fields",
            "type": "Number",
            "optional": true,
            "field": "velikost_polja",
            "description": "<p>Height size in lines of text field (for text question) (default is single line)</p>"
          },
          {
            "group": "Question fields",
            "type": "String[]",
            "optional": true,
            "field": "Odgovori",
            "description": "<p>Array of options to add to question (for radio and checkbox)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Post-example (For survey): ",
          "content": "      {\n\t\"naslov_vprasalnika\": \"This is title of new survey\",\n\t\"survey_type\": 2,\n\t\"uvod\": {\n\t\t\"besedilo\": \"This is text of intruduction\",\n\t\t\"hide_uvod\": 0\n\t},\n\t\"zakljucek\": {\n\t\t\"besedilo\": \"\",\n\t\t\"hide_zakljucek\": 1\n\t},\n\t\"vprasanja\": [{\n\t\t\t\"besedilo_vprasanja\": \"This is text of question number 1\",\n\t\t\t\"mesto_vprasanja\": 1,\n\t\t\t\"vrsta_vprasanja\": 1,\n\t\t\t\"reminder\": 0,\n\t\t\t\"other\": \"Other:\",\n\t\t\t\"Odgovori\": [\"Text of option 1\", \"Text of option 2\", \"Text of option 3\"]\n\t\t},\n\t\t{\n\t\t\t\"besedilo_vprasanja\": \"This is text of question number 2\",\n\t\t\t\"mesto_vprasanja\": 2,\n\t\t\t\"vrsta_vprasanja\": 2,\n\t\t\t\"velikost_polja\": 10,\n\t\t\t\"reminder\": 1\n\t\t},\n                {\n\t\t\t\"besedilo_vprasanja\": \"This is text of question number 3\",\n\t\t\t\"mesto_vprasanja\": 3,\n\t\t\t\"vrsta_vprasanja\": 0,\n\t\t\t\"Odgovori\": [\"Text of option 1\", \"Text of option 2\", \"Text of option 3\"]\n\t\t}\n\t]\n      }",
          "type": "json"
        },
        {
          "title": "Post-example (For voting): ",
          "content": "      {\n\t\"naslov_vprasalnika\": \"This is title of new survey\",\n\t\"survey_type\": 0,\n        \"besedilo_vprasanja\": \"This is text of question number 1\",\n        \"vrsta_vprasanja\": 0,\n        \"other\": \"Other:\",\n        \"Odgovori\": [\"Text of option 1\", \"Text of option 2\", \"Text of option 3\"]\n      }",
          "type": "json"
        }
      ]
    },
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "url",
            "description": "<p>Link to new survey</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "id",
            "description": "<p>ID of new survey</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\"url\":\"http:\\/\\/141.255.212.38\\/1ka\\/a\\/56\",\"id\":56,\"note\":\"Survey created\"}",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Surveys"
  },
  {
    "type": "delete",
    "url": "https://www.1ka.si/api/deleteSurvey/survey/:id",
    "title": "deleteSurvey",
    "name": "deleteSurvey",
    "group": "Surveys",
    "description": "<p>Delete survey</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "GET parameter": [
          {
            "group": "GET parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey to delete</p>"
          }
        ]
      }
    },
    "success": {
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\"note\":\"Survey deleted\"}",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Surveys"
  },
  {
    "type": "get",
    "url": "https://www.1ka.si/api/getSurvey/survey/:id",
    "title": "getSurvey",
    "name": "getSurvey",
    "group": "Surveys",
    "description": "<p>Get info of survey and its questions</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "Main Fields": [
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "link",
            "description": "<p>Link of survey</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "title",
            "description": "<p>Title of survey</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "intro",
            "description": "<p>Introduction text (&quot;&quot; means default)</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "concl",
            "description": "<p>Conclusion text (&quot;&quot; means default)</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "show_intro",
            "description": "<p>Hide or show introduction (0-hide, 1-show)</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "show_concl",
            "description": "<p>Hide or show conclusion (0-hide, 1-show)</p>"
          },
          {
            "group": "Main Fields",
            "type": "String",
            "optional": false,
            "field": "page_id",
            "description": "<p>ID of last page in survey</p>"
          },
          {
            "group": "Main Fields",
            "type": "Object[]",
            "optional": false,
            "field": "questions",
            "description": "<p>Array of all questions in survey</p>"
          }
        ],
        "Question Fields": [
          {
            "group": "Question Fields",
            "type": "String",
            "optional": false,
            "field": "id",
            "description": "<p>Id of question in survey</p>"
          },
          {
            "group": "Question Fields",
            "type": "String",
            "optional": false,
            "field": "type",
            "description": "<p>Type in text of question in survey</p>"
          },
          {
            "group": "Question Fields",
            "type": "String",
            "optional": false,
            "field": "type_code",
            "description": "<p>Type in code of question in survey</p>"
          },
          {
            "group": "Question Fields",
            "type": "String",
            "optional": false,
            "field": "title",
            "description": "<p>Title/text of question in survey</p>"
          },
          {
            "group": "Question Fields",
            "type": "String",
            "optional": false,
            "field": "info",
            "description": "<p>Additional information of question (e.g. &quot;Multiple answers are possible&quot;)</p>"
          },
          {
            "group": "Question Fields",
            "type": "String",
            "optional": false,
            "field": "variable",
            "description": "<p>Short mark of question in survey (question name)</p>"
          },
          {
            "group": "Question Fields",
            "type": "String",
            "optional": false,
            "field": "page_id",
            "description": "<p>ID of page that question is at</p>"
          },
          {
            "group": "Question Fields",
            "type": "String",
            "optional": false,
            "field": "page_title",
            "description": "<p>Name/text of page that question is at</p>"
          },
          {
            "group": "Question Fields",
            "type": "String",
            "optional": false,
            "field": "reminder",
            "description": "<p>Reminder of question (0-no reminder, 1-soft reminder, 2-hard reminder)</p>"
          },
          {
            "group": "Question Fields",
            "type": "String",
            "optional": false,
            "field": "order",
            "description": "<p>Sequence number of question in page</p>"
          },
          {
            "group": "Question Fields",
            "type": "String",
            "optional": false,
            "field": "params",
            "description": "<p>Additional params as string for question</p>"
          },
          {
            "group": "Question Fields",
            "type": "Object[]",
            "optional": false,
            "field": "options",
            "description": "<p>Array of options/answers/values of question</p>"
          }
        ],
        "Value Fields": [
          {
            "group": "Value Fields",
            "type": "String",
            "optional": false,
            "field": "id",
            "description": "<p>Id of value in question</p>"
          },
          {
            "group": "Value Fields",
            "type": "String",
            "optional": false,
            "field": "title",
            "description": "<p>Title of value in question</p>"
          },
          {
            "group": "Value Fields",
            "type": "String",
            "optional": false,
            "field": "variable",
            "description": "<p>Short mark of value in question (value name)</p>"
          },
          {
            "group": "Value Fields",
            "type": "String",
            "optional": false,
            "field": "other",
            "description": "<p>Is this value other (0-basic, 1-other)</p>"
          },
          {
            "group": "Value Fields",
            "type": "String",
            "optional": false,
            "field": "order",
            "description": "<p>Sequence number of value in the question</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\n           \"link\": \"http:\\/\\/192.168.0.101\\/1ka\\/a\\/109\",\n           \"title\": \"A survey\",\n           \"intro\": \"\",\n           \"concl\": \"\",\n           \"show_intro\": \"1\",\n           \"show_concl\": \"1\",\n           \"page_id\": \"135\",\n           \"questions\": [{\n                   \"id\": \"487\",\n                   \"type\": \"Single answer\",\n                   \"type_code\": \"1\",\n                   \"title\": \"City\",\n                   \"info\": \"\",\n                   \"variable\": \"Q1\",\n                   \"page_id\": \"134\",\n                   \"page_title\": \"Stran 1\",\n                   \"reminder\": \"0\",\n                   \"orientation\": \"1\",\n                   \"order\": \"1\",\n                   \"params\": [],\n                   \"options\": [{\n                           \"id\": \"1438\",\n                           \"title\": \"Ljubljana\",\n                           \"variable\": \"1\",\n                           \"other\": \"0\",\n                           \"order\": \"1\"\n                   }, {\n                           \"id\": \"1439\",\n                           \"title\": \"Berlin\",\n                           \"variable\": \"2\",\n                           \"other\": \"0\",\n                           \"order\": \"2\"\n                   }, {\n                           \"id\": \"1440\",\n                           \"title\": \"London\",\n                           \"variable\": \"3\",\n                           \"other\": \"0\",\n                           \"order\": \"3\"\n                   }, {\n                           \"id\": \"1445\",\n                           \"title\": \"Other:\",\n                           \"variable\": \"4\",\n                           \"other\": \"1\",\n                           \"order\": \"4\"\n                   }]\n           }, {\n                   \"id\": \"488\",\n                   \"type\": \"Multiple answer\",\n                   \"type_code\": \"2\",\n                   \"title\": \"Country\",\n                   \"info\": \"Multiple answers possible\",\n                   \"variable\": \"Q2\",\n                   \"page_id\": \"134\",\n                   \"page_title\": \"Stran 1\",\n                   \"reminder\": \"0\",\n                   \"orientation\": \"1\",\n                   \"order\": \"2\",\n                   \"params\": [],\n                   \"options\": [{\n                           \"id\": \"1441\",\n                           \"title\": \"Slovenia\",\n                           \"variable\": \"Q2a\",\n                           \"other\": \"0\",\n                           \"order\": \"1\"\n                   }, {\n                           \"id\": \"1442\",\n                           \"title\": \"Germany\",\n                           \"variable\": \"Q2b\",\n                           \"other\": \"0\",\n                           \"order\": \"2\"\n                   }, {\n                           \"id\": \"1443\",\n                           \"title\": \"UK\",\n                           \"variable\": \"Q2c\",\n                           \"other\": \"0\",\n                           \"order\": \"3\"\n                   }, {\n                           \"id\": \"1446\",\n                           \"title\": \"Other:\",\n                           \"variable\": \"Q2d\",\n                           \"other\": \"1\",\n                           \"order\": \"4\"\n                   }]\n           }, {\n                   \"id\": \"489\",\n                   \"type\": \"Text input\",\n                   \"type_code\": \"21\",\n                   \"title\": \"Write a name\",\n                   \"info\": \"\",\n                   \"variable\": \"Q3\",\n                   \"page_id\": \"135\",\n                   \"page_title\": \"Page 2\",\n                   \"reminder\": \"0\",\n                   \"orientation\": \"1\",\n                   \"order\": \"1\",\n                   \"params\": {\n                           \"taWidth\": \"-1\",\n                           \"taSize\": \"1\",\n                           \"captcha\": \"0\",\n                           \"emailVerify\": \"0\",\n                           \"prevAnswers\": \"0\",\n                           \"disabled_vprasanje\": \"0\"\n                   },\n                   \"options\": [{\n                           \"id\": \"1444\",\n                           \"title\": \"Input text\",\n                           \"variable\": \"Q3a\",\n                           \"other\": \"0\",\n                           \"order\": \"1\"\n                   }]\n           }]\n       }",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Surveys"
  },
  {
    "type": "get",
    "url": "https://www.1ka.si/api/getSurveyInfo/survey/:id",
    "title": "getSurveyInfo",
    "name": "getSurveyInfo",
    "group": "Surveys",
    "description": "<p>Get info of survey</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "type": "Number",
            "optional": false,
            "field": "count",
            "description": "<p>Number of surveys in list</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "link",
            "description": "<p>Access link of survey for respondents</p>"
          },
          {
            "group": "Success 200",
            "type": "Object[]",
            "optional": false,
            "field": "surveys",
            "description": "<p>Array of surveys</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "id",
            "description": "<p>ID of survey</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "naslov",
            "description": "<p>Title of survey</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "active",
            "description": "<p>Current activity of survey (1 – survey is active, 0 – survey is not active)</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "block_ip",
            "description": "<p>Blocked IP in minutes – 0 off (1440 = 24h) - if on, respondent can not access to survey again for given minutes</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "e_name",
            "description": "<p>Name of editor of survey</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "i_name",
            "description": "<p>Name of author of survey</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "e_time",
            "description": "<p>Last edited</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "i_time",
            "description": "<p>Created</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "v_time_first",
            "description": "<p>First entry</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "v_time_last",
            "description": "<p>Last entry</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "answers",
            "description": "<p>Number of units</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "variables",
            "description": "<p>Number of questions</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "lastingfrom",
            "description": "<p>Date of start survey duration</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "lastinguntill",
            "description": "<p>Date of end survey duration</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "survey_type",
            "description": "<p>Type of survey (2-survey, 0-voting, 1-form)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Success-Response:",
          "content": " {\"count\":1,\n       \"surveys\":[\n          {\"id\":\"29\",\n          \"folder\":\"1\",\n          \"del\":\"1\",\n          \"naslov\":\"Test 111\",\n          \"active\":\"1\",\n          \"mobile_created\":\"0\",\n          \"block_ip\":\"0\",\n          \"edit_uid\":\"1045\",\n          \"e_name\":\"admin\",\n          \"e_surname\":\"admin\",\n          \"e_email\":\"admin\",\n          \"insert_uid\":\"1045\",\n          \"i_name\":\"admin\",\n          \"i_surname\":\"admin\",\n          \"i_email\":\"admin\",\n          \"e_time\":\"08.11.18 11:36\",\n          \"i_time\":\"27.07.18 11:36\",\n          \"v_time_first\":\"27.07.18 14:31\",\n          \"v_time_last\":\"20.08.18 9:33\",\n          \"answers\":\"8\",\n          \"approp\":\"7\",\n          \"variables\":\"12\",\n          \"trajanjeod\":\"08.11.18\",\n          \"trajanjedo\":\"08.02.19\",\n          \"survey_type\":\"2\"}\n  ],\n  \"link\":\"http:\\/\\/www.1ka.si\\/a\\/109\"\n}",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Surveys"
  },
  {
    "type": "get",
    "url": "https://www.1ka.si/api/getSurveyList?limit=3",
    "title": "getSurveyList",
    "name": "getSurveyList",
    "group": "Surveys",
    "description": "<p>Get list of info of all surveys</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "Parameter": [
          {
            "group": "Parameter",
            "type": "Number",
            "optional": false,
            "field": "limit",
            "description": "<p>Optional Limit of surveys to return, DESC order by time of new input (answer)</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "type": "Number",
            "optional": false,
            "field": "count",
            "description": "<p>Number of surveys in list</p>"
          },
          {
            "group": "Success 200",
            "type": "Object[]",
            "optional": false,
            "field": "surveys",
            "description": "<p>Array of surveys</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "id",
            "description": "<p>ID of survey</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "naslov",
            "description": "<p>Title of survey</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "active",
            "description": "<p>Current activity of survey (1 – survey is active, 0 – survey is not active)</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "block_ip",
            "description": "<p>Blocked IP in minutes – 0 off (1440 = 24h) - if on, respondent can not access to survey again for given minutes</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "e_name",
            "description": "<p>Name of editor of survey</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "i_name",
            "description": "<p>Name of author of survey</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "e_time",
            "description": "<p>Last edited</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "i_time",
            "description": "<p>Created</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "v_time_first",
            "description": "<p>First entry</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "v_time_last",
            "description": "<p>Last entry</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "answers",
            "description": "<p>Number of units</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "variables",
            "description": "<p>Number of questions</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "lastingfrom",
            "description": "<p>Date of start survey duration</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "lastinguntill",
            "description": "<p>Date of end survey duration</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "survey_type",
            "description": "<p>Type of survey (2-survey, 0-voting, 1-form)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\"count\":3,\n      \"surveys\":[\n         {\"id\":\"29\",\n         \"folder\":\"1\",\n         \"del\":\"1\",\n         \"naslov\":\"Test 111\",\n         \"active\":\"1\",\n         \"mobile_created\":\"0\",\n         \"block_ip\":\"0\",\n         \"edit_uid\":\"1045\",\n         \"e_name\":\"admin\",\n         \"e_surname\":\"admin\",\n         \"e_email\":\"admin\",\n         \"insert_uid\":\"1045\",\n         \"i_name\":\"admin\",\n         \"i_surname\":\"admin\",\n         \"i_email\":\"admin\",\n         \"e_time\":\"08.11.18 11:36\",\n         \"i_time\":\"27.07.18 11:36\",\n         \"v_time_first\":\"27.07.18 14:31\",\n         \"v_time_last\":\"20.08.18 9:33\",\n         \"answers\":\"8\",\n         \"approp\":\"7\",\n         \"variables\":\"12\",\n         \"trajanjeod\":\"08.11.18\",\n         \"trajanjedo\":\"08.02.19\",\n         \"survey_type\":\"2\"},...\n ]}",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Surveys"
  },
  {
    "type": "get",
    "url": "https://www.1ka.si/api/getSurveyResponses",
    "title": "getSurveyResponses",
    "name": "getSurveyResponses",
    "group": "Surveys",
    "description": "<p>Get list of numbers of all surveys responses (and info about activity) with keys as survey ID</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "answers",
            "description": "<p>Number of all responses</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "active",
            "description": "<p>Is survey active right now (1-active, 0-unactive)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\n\t\"4401\": {\n\t\t\"answers\": \"1103\",\n\t\t\"active\": \"0\"\n\t},\n\t\"5012\": {\n\t\t\"answers\": \"190\",\n\t\t\"active\": \"1\"\n\t},\n\t\"5330\": {\n\t\t\"answers\": \"88\",\n\t\t\"active\": \"1\"\n\t}\n}",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Surveys"
  },
  {
    "type": "post",
    "url": "https://www.1ka.si/api/updateSurvey/survey/:id",
    "title": "updateSurvey",
    "name": "updateSurvey",
    "group": "Surveys",
    "description": "<p>Update basic survey properties</p>",
    "header": {
      "fields": {
        "Header": [
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "identifier",
            "description": "<p>Identifier to access API (https://www.1ka.si/d/en/about/1ka-api/api-key)</p>"
          },
          {
            "group": "Header",
            "type": "String",
            "optional": false,
            "field": "token",
            "description": "<p>SHA256 hash token calculated with API key (https://www.1ka.si/d/en/about/1ka-api/example2/get-call-example-php)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Request-Example:",
          "content": "{ \"identifier\": \"abcdefgh01234567\",\n  \"token\": \"bd26lo2863dzcyidb8d7rmwo7xydhpoa77kbdamwtlj5ej70akgffb0b7aj30zqh\" }",
          "type": "json"
        }
      ]
    },
    "parameter": {
      "fields": {
        "GET parameter": [
          {
            "group": "GET parameter",
            "type": "Number",
            "optional": false,
            "field": "id",
            "description": "<p>Id of survey</p>"
          }
        ],
        "POST parameter": [
          {
            "group": "POST parameter",
            "type": "String",
            "optional": true,
            "field": "title",
            "description": "<p>Title of survey</p>"
          },
          {
            "group": "POST parameter",
            "type": "String",
            "optional": true,
            "field": "que_title",
            "description": "<p>Title/text of question (only voting)</p>"
          },
          {
            "group": "POST parameter",
            "type": "String",
            "optional": true,
            "field": "introduction",
            "description": "<p>Introduction text of survey or form (&quot;&quot; stands for default text)</p>"
          },
          {
            "group": "POST parameter",
            "type": "String",
            "optional": true,
            "field": "conclusion",
            "description": "<p>Conclusion text of survey or form (&quot;&quot; stands for default text)</p>"
          },
          {
            "group": "POST parameter",
            "type": "String",
            "optional": true,
            "field": "show_intro",
            "description": "<p>Do we show introduction (0-hide, 1-show)</p>"
          },
          {
            "group": "POST parameter",
            "type": "String",
            "optional": true,
            "field": "show_concl",
            "description": "<p>Do we show conclusion (0-hide, 1-show)</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "Post-example (survey or form): ",
          "content": "{\n  \"title\":\"A survey\",\n  \"introduction\":\"\",\n  \"conclusion\":\"Thank you!\",\n  \"show_intro\":\"1\",\n  \"show_concl\":\"1\"\n}",
          "type": "json"
        },
        {
          "title": "Post-example (voting): ",
          "content": "{\n  \"title\":\"Weekly voting\",\n  \"que_title\":\"What is your vote?\"\n}",
          "type": "json"
        }
      ]
    },
    "success": {
      "examples": [
        {
          "title": "Success-Response:",
          "content": "{\"note\":\"Survey updated\"}",
          "type": "json"
        }
      ]
    },
    "version": "1.0.0",
    "filename": "./class.ApiSurvey.php",
    "groupTitle": "Surveys"
  }
] });
