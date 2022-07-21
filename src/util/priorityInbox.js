/**
 * @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/** Search query for important and unread messages inside priority inbox */
export const priorityImportantQuery = 'is:pi-important'

/** Search query for starred messages inside priority inbox */
export const priorityStarredQuery = 'is:pi-starred'

/** Search query for other messages inside priority inbox */
export const priorityOtherQuery = 'is:pi-other'

/**
 * Return an array of all search queries inside the priority inbox
 *
 * @return {(string)[]}
 */
export function getPrioritySearchQueries() {
	return [
		priorityImportantQuery,
		priorityStarredQuery,
		priorityOtherQuery,
	]
}
