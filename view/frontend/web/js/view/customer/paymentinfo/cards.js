/**
 * Copyright © 2015-present ParadoxLabs, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Need help? Try our knowledgebase and support system:
 * @link https://support.paradoxlabs.com
 */

require(
    [
        'jquery',
        'Magento_Ui/js/modal/confirm'
    ],
    function ($, confirmation) {
        $('.delete').on('click', function (e) {
            e.preventDefault();
            confirmation({
                title: 'Delete payment option',
                content: 'Are you sure you want to delete this card?',
                actions: {
                    confirm: function () {
                        $.ajax({
                            showLoader: true,
                            url: e.currentTarget.href,
                            type: "POST",
                            success: function (data) {
                                // TODO: Replace correct block, or add id's to card divs and remove them?
                                $('.main').html(data.result);
                            },
                            error: function () {
                                // TODO: Get traditional message as error.
                            }
                        });
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        });
    }
);
