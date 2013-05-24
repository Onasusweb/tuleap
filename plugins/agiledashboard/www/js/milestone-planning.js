/* 
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
 * 
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
 * 
 * This file is a part of Tuleap.
 * 
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
var tuleap = tuleap || { };
tuleap.agiledashboard = tuleap.agiledashboard || { };
   
(function ($) {
    tuleap.agiledashboard.NewPlanning = Class.create({
        initialize: function (container) {
            var self = this;

            console.log($('.agiledashboard-planning-submilestone-header'));
            $('.agiledashboard-planning-submilestone-header').click(function (event) {
                    console.log('here');
                var $data_container,
                    $submilestone_content_row = $(this).next();

                if ($submilestone_content_row.is(':visible')) {
                    $submilestone_content_row.hide();
                } else {
                    data_container = $submilestone_content_row.find(".submilestone-data");

                    if (! isSubmilestoneDataLoaded(data_container)) {
                        self.fetchSubmilestoneData(data_container);
                    }
                    $submilestone_content_row.show();
                }
                $(this).find('i').toggleClass('icon-chevron-down').toggleClass('icon-chevron-right');
                event.preventDefault();
            });
            $('.agiledashboard-planning-submilestone-header').next().hide();

            function isSubmilestoneDataLoaded(data_container) {
                return data_container.attr("data-loaded") === "true";
            }

            this.makeSubmilestonesSortable();
        },

        fetchSubmilestoneData : function(data_container) {
            var self = this;

            $.ajax({
                url : "/plugins/agiledashboard/?action=submilestonedata",
                dataType : "html",
                data : {
                    planning_id: data_container.attr('data-planning-id'),
                    aid : data_container.attr('data-submilestone-id')
                },
                method : "get",
                success : function(data) {
                    self.setSubmilestoneDataLoaded(data_container);
                    data_container.find('tbody').append(data);
                    self.setSubmilestonesEditLinks(data_container);
                    self.updateSubmilestoneCapacity(data_container);
                    self.makeSubmilestonesSortable(data_container);
                },
                error : function(data) {
                    console.log('error', data);
                }
            });
         },

        setSubmilestoneDataLoaded : function(data_container) {
            data_container.attr("data-loaded", "true");
        },

        setSubmilestonesEditLinks : function(data_container) {
            var urls                  = $('tr.submilestone-element td > a', data_container);
            var milestone_id          = this.getMilestoneId();
            var milestone_planning_id = this.getMilestonePlanningId();

            urls.each( function(index, url) {
                var new_url = $(url).attr('href') + '&' + 'planning[planning][' + milestone_planning_id + ']=' + milestone_id;

                $(url).attr('href', new_url);
            });

        },

        getMilestoneId : function() {
            return $('div.agiledashboard-planning-backlog').attr('data-milestone-id');
        },

        getMilestonePlanningId : function() {
            return $('div.agiledashboard-planning-backlog').attr('data-milestone-planning-id');
        },

        updateSubmilestoneCapacity : function(data_container) {
            var capacity = 0,
                capacities = data_container.find(".submilestone-element-capacity");

            capacities.each(function(){
                var element_capacity = parseFloat($(this).html());
                if (! isNaN(element_capacity)) {
                    capacity += parseFloat(element_capacity);
                }
            });

            data_container.find(".submilestone-capacity").html(capacity);
        },

        makeSubmilestonesSortable : function(data_container) {
            var self = this,
                from_submilestone;

            $( ".submilestone-element-rows" ).sortable({
                connectWith: ".submilestone-element-rows",
                dropOnEmpty: true,
                scroll: true,
                tolerance : "pointer",
                scrollSensitivity: 50,
                items : ".submilestone-element",
                start : function (event, ui) {
                    from_submilestone = $(event.target).parents(".submilestone-data").first().attr('data-submilestone-id');
                },
                stop: function (event, ui) {
                    var rowIdentifier = "data-artifact-id",
                        item = ui.item,
                        submilestone_id,
                        association,
                        item_id = $(item).attr(rowIdentifier),
                        next_id = $(item).next(".submilestone-element").attr(rowIdentifier),
                        prev_id = $(item).prev(".submilestone-element").attr(rowIdentifier);

                        association = getMainFunc();

                        if(association === "" ) {
                            if (next_id) {
                                sortHigher(item_id, next_id);
                            } else if (prev_id) {
                                sortLesser(item_id, prev_id);
                            }
                        } else {
                            $.ajax({
                                url  : codendi.tracker.base_url,
                                data : {
                                    "func"              : association,
                                    "aid"               : item_id,
                                    "linked-artifact-id": getSubmilestoneId()
                                },
                                method : "get",
                                success : function() {
                                    if (next_id) {
                                        sortHigher(item_id, next_id);
                                    } else if (prev_id) {
                                        sortLesser(item_id, prev_id);
                                    }
                                }
                            });
                        }
                        
                        self.updateSubmilestoneCapacity(data_container);

                        function getMainFunc() {
                            var submilestoneid = $(ui.item).parents(".submilestone-data").first().attr('data-submilestone-id');

                            if(submilestoneid == from_submilestone) {
                                return ""
                            } else if (typeof(submilestoneid) === "undefined") {
                                return "unassociate-artifact-to"
                            } else {
                                return "associate-artifact-to"
                            }
                        }

                        function getSubmilestoneId() {
                            submilestone_id = $(ui.item).parents(".submilestone-data").first().attr('data-submilestone-id');

                            if(typeof(submilestone_id) === "undefined") {
                                return from_submilestone;
                            }

                            return submilestone_id;
                        }

                        function sortHigher(source_id, target_id) {
                            updateOrder('higher-priority-than', source_id, target_id);
                        }

                        function sortLesser(source_id, target_id) {
                            updateOrder('lesser-priority-than', source_id, target_id);
                        }

                        function updateOrder(func, source_id, target_id) {
                            $.ajax({
                                url  : codendi.tracker.base_url,
                                data : {
                                    "func"             : func,
                                    "aid"              : source_id,
                                    "target-id"        : target_id
                                },
                                method : "get"
                            })
                        }
                }
            }).disableSelection();   
        }
    });
})(jQuery);
